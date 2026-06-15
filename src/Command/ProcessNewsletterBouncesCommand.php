<?php

declare(strict_types=1);

namespace App\Command;

use App\Marketing\Entity\NewsletterDelivery;
use App\Marketing\Entity\NewsletterSubscription;
use App\Marketing\Repository\NewsletterDeliveryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webklex\PHPIMAP\ClientManager;

#[AsCommand(
    name: 'app:newsletter:process-bounces',
    description: 'Leest en verwerkt technische retourberichten uit de bounce-mailbox.',
)]
final class ProcessNewsletterBouncesCommand extends Command
{
    private const PROCESSED_DELIVERY_STATUSES = [
        NewsletterDelivery::STATUS_HARD_BOUNCE,
        NewsletterDelivery::STATUS_SOFT_BOUNCE,
        NewsletterDelivery::STATUS_TECHNICAL_FAILURE,
        NewsletterDelivery::STATUS_REVIEW,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NewsletterDeliveryRepository $deliveryRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'apply',
                null,
                InputOption::VALUE_NONE,
                'Voer de databasewijzigingen daadwerkelijk uit.'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Lees ook berichten die al als gelezen zijn gemarkeerd.'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $apply = (bool) $input->getOption('apply');
        $includeAll = (bool) $input->getOption('all');

        if ($apply && $includeAll) {
            $io->warning(
                'Je verwerkt ook reeds gelezen berichten. Gebruik --all --apply alleen eenmalig voor de bestaande mailbox.'
            );
        }

        $host = $_ENV['BOUNCE_IMAP_HOST'] ?? null;
        $port = (int) ($_ENV['BOUNCE_IMAP_PORT'] ?? 993);
        $encryption = $_ENV['BOUNCE_IMAP_ENCRYPTION'] ?? 'ssl';
        $username = $_ENV['BOUNCE_IMAP_USERNAME'] ?? null;
        $password = $_ENV['BOUNCE_IMAP_PASSWORD'] ?? null;
        $folderName = $_ENV['BOUNCE_IMAP_FOLDER'] ?? 'INBOX';

        if (
            !is_string($host)
            || $host === ''
            || !is_string($username)
            || $username === ''
            || !is_string($password)
            || $password === ''
        ) {
            $io->error('De bounce-IMAP-configuratie is niet compleet.');

            return Command::FAILURE;
        }

        $clientManager = new ClientManager();

        $client = $clientManager->make([
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'validate_cert' => true,
            'username' => $username,
            'password' => $password,
            'protocol' => 'imap',
        ]);

        try {
            $client->connect();

            $folder = $client->getFolder($folderName);

            if ($folder === null) {
                $io->error(sprintf(
                    'De IMAP-map "%s" is niet gevonden.',
                    $folderName
                ));

                return Command::FAILURE;
            }

            $query = $folder->messages();

            $messages = $includeAll
                ? $query->all()->get()
                : $query->unseen()->get();

            $rows = [];

            $hardCount = 0;
            $softCount = 0;
            $technicalCount = 0;
            $reviewCount = 0;
            $nonBounceCount = 0;
            $deliveryNotFoundCount = 0;
            $subscriptionNotFoundCount = 0;
            $alreadyProcessedCount = 0;
            $appliedCount = 0;
            $errorCount = 0;

            foreach ($messages as $message) {
                try {
                    $rawSubject = (string) $message->getSubject();

                    $decodedSubject = iconv_mime_decode(
                        $rawSubject,
                        ICONV_MIME_DECODE_CONTINUE_ON_ERROR,
                        'UTF-8'
                    );

                    $subject = is_string($decodedSubject)
                        ? $decodedSubject
                        : $rawSubject;

                    $textBody = (string) ($message->getTextBody() ?? '');
                    $rawBody = (string) $message->getRawBody();
                    $content = $textBody . "\n" . $rawBody;

                    $recipient = $this->extractRecipient($content);

                    /*
                     * Een bericht zonder bounce-ontvanger is bijvoorbeeld
                     * een configuratiebericht in de bounce-mailbox.
                     */
                    if ($recipient === null) {
                        ++$nonBounceCount;

                        if ($apply) {
                            $this->markMessageAsSeen($message);
                        }

                        continue;
                    }

                    $status = $this->extractStatus($content);
                    $diagnostic = $this->extractDiagnostic($content);
                    $classification = $this->classifyBounce(
                        $status,
                        $diagnostic
                    );

                    match ($classification) {
                        'hard' => ++$hardCount,
                        'soft' => ++$softCount,
                        'technical' => ++$technicalCount,
                        default => ++$reviewCount,
                    };

                    $bouncedAt = $this->extractMessageDate($message);

                    $deliveryToken = $this->extractDeliveryToken(
                        $content
                    );

                    $messageId = $this->extractOriginalMessageId(
                        $content
                    );

                    $delivery = null;
                    $matchMethod = 'geen';

                    if ($deliveryToken !== null) {
                        $delivery = $this->deliveryRepository
                            ->findOneByDeliveryToken($deliveryToken);

                        if ($delivery !== null) {
                            $matchMethod = 'delivery-token';
                        }
                    }

                    if ($delivery === null && $messageId !== null) {
                        $delivery = $this->deliveryRepository
                            ->findOneByMessageId($messageId);

                        if ($delivery !== null) {
                            $matchMethod = 'message-id';
                        }
                    }

                    if ($delivery === null) {
                        $delivery = $this->deliveryRepository
                            ->findLatestAcceptedForRecipient(
                                $recipient,
                                $bouncedAt
                            );

                        if ($delivery !== null) {
                            $matchMethod = 'e-mailadres';
                        }
                    }

                    if ($delivery === null) {
                        ++$deliveryNotFoundCount;
                    }

                    $subscription = $delivery?->getSubscription();

                    if (!$subscription instanceof NewsletterSubscription) {
                        /** @var NewsletterSubscription|null $subscription */
                        $subscription = $this->entityManager
                            ->getRepository(
                                NewsletterSubscription::class
                            )
                            ->findOneBy([
                                'email' => $recipient,
                            ]);
                    }

                    if ($subscription === null) {
                        ++$subscriptionNotFoundCount;
                    }

                    $alreadyProcessed = $delivery !== null
                        && in_array(
                            $delivery->getStatus(),
                            self::PROCESSED_DELIVERY_STATUSES,
                            true
                        );

                    if ($alreadyProcessed) {
                        ++$alreadyProcessedCount;
                    }

                    $deliveryStatus = $delivery?->getStatus()
                        ?? 'Niet gevonden';

                    $subscriptionStatus = match (true) {
                        $subscription === null => 'Niet gevonden',
                        $subscription->isActive() => 'Actief',
                        default => 'Inactief',
                    };

                    $action = $this->describeAction(
                        $classification,
                        $delivery,
                        $subscription,
                        $alreadyProcessed
                    );

                    if ($apply) {
                        $this->applyBounce(
                            message: $message,
                            delivery: $delivery,
                            subscription: $subscription,
                            classification: $classification,
                            diagnostic: $diagnostic,
                            bouncedAt: $bouncedAt,
                            alreadyProcessed: $alreadyProcessed,
                        );

                        ++$appliedCount;
                    }

                    $rows[] = [
                        $recipient,
                        $status ?? '-',
                        $classification,
                        $matchMethod,
                        $deliveryStatus,
                        $subscriptionStatus,
                        $action,
                        $this->truncate(
                            $diagnostic ?? $subject,
                            70
                        ),
                    ];
                } catch (\Throwable $exception) {
                    ++$errorCount;

                    $rows[] = [
                        'Onbekend',
                        '-',
                        'error',
                        '-',
                        '-',
                        '-',
                        'Niet verwerkt',
                        $this->truncate(
                            $exception->getMessage(),
                            70
                        ),
                    ];
                }
            }

            $io->title(
                $apply
                    ? 'Nieuwsbriefbounces — verwerking'
                    : 'Nieuwsbriefbounces — veilige controle'
            );

            $io->table(
                [
                    'E-mailadres',
                    'DSN',
                    'Classificatie',
                    'Koppeling',
                    'Delivery',
                    'Inschrijving',
                    'Actie',
                    'Reden',
                ],
                $rows
            );

            $io->definitionList(
                ['Gelezen berichten' => count($messages)],
                ['Geen bouncebericht' => $nonBounceCount],
                ['Hard bounces' => $hardCount],
                ['Soft bounces' => $softCount],
                ['Technische fouten' => $technicalCount],
                ['Handmatig beoordelen' => $reviewCount],
                ['Delivery niet gevonden' => $deliveryNotFoundCount],
                ['Inschrijving niet gevonden' => $subscriptionNotFoundCount],
                ['Al verwerkt' => $alreadyProcessedCount],
                ['Werkelijk toegepast' => $appliedCount],
                ['Verwerkingsfouten' => $errorCount],
            );

            if ($apply) {
                $io->success(
                    'De verwerkbare bounces zijn opgeslagen en de bijbehorende mailboxberichten zijn als gelezen gemarkeerd.'
                );
            } else {
                $io->note(
                    'Er zijn geen databasewijzigingen uitgevoerd. Gebruik --apply om de resultaten op te slaan.'
                );
            }

            $client->disconnect();

            return $errorCount === 0
                ? Command::SUCCESS
                : Command::FAILURE;
        } catch (\Throwable $exception) {
            $io->error(sprintf(
                'Verwerking mislukt: %s',
                $exception->getMessage()
            ));

            return Command::FAILURE;
        }
    }

    private function applyBounce(
        object $message,
        ?NewsletterDelivery $delivery,
        ?NewsletterSubscription $subscription,
        string $classification,
        ?string $diagnostic,
        \DateTimeImmutable $bouncedAt,
        bool $alreadyProcessed,
    ): void {
        $connection = $this->entityManager->getConnection();

        $connection->beginTransaction();

        $seenWasSet = false;

        try {
            if (!$alreadyProcessed) {
                if ($delivery !== null) {
                    match ($classification) {
                        'hard' => $delivery->markHardBounce(
                            $diagnostic,
                            $bouncedAt
                        ),
                        'soft' => $delivery->markSoftBounce(
                            $diagnostic,
                            $bouncedAt
                        ),
                        'technical' => $delivery->markTechnicalFailure(
                            $diagnostic,
                            $bouncedAt
                        ),
                        default => $delivery->markForReview(
                            $diagnostic,
                            $bouncedAt
                        ),
                    };
                }

                if ($subscription !== null) {
                    match ($classification) {
                        'hard' => $subscription->markHardBounce(
                            $diagnostic,
                            $bouncedAt
                        ),
                        'soft' => $subscription->markSoftBounce(
                            $diagnostic,
                            $bouncedAt
                        ),
                        default => null,
                    };
                }
            }

            $this->entityManager->flush();

            $this->markMessageAsSeen($message);
            $seenWasSet = true;

            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            if ($seenWasSet) {
                try {
                    $message->unsetFlag('Seen');
                } catch (\Throwable) {
                    // De databasewijziging is teruggedraaid; een mislukte
                    // IMAP-reset wordt bij een volgende controle zichtbaar.
                }
            }

            $this->entityManager->clear();

            throw $exception;
        }
    }

    private function markMessageAsSeen(object $message): void
    {
        if ($message->setFlag('Seen') !== true) {
            throw new \RuntimeException(
                'Het bouncebericht kon niet als gelezen worden gemarkeerd.'
            );
        }
    }

    private function describeAction(
        string $classification,
        ?NewsletterDelivery $delivery,
        ?NewsletterSubscription $subscription,
        bool $alreadyProcessed,
    ): string {
        if ($alreadyProcessed) {
            return 'Al verwerkt';
        }

        return match ($classification) {
            'hard' => match (true) {
                $delivery !== null && $subscription !== null =>
                    'Delivery hard bounce; inschrijving deactiveren',
                $subscription !== null =>
                    'Geen delivery; inschrijving deactiveren',
                default =>
                    'Hard bounce registreren indien mogelijk',
            },
            'soft' => match (true) {
                $delivery !== null && $subscription !== null =>
                    'Soft bounce registreren; actief laten',
                $subscription !== null =>
                    'Geen delivery; soft bounce registreren',
                default =>
                    'Soft bounce zonder koppeling',
            },
            'technical' => $delivery !== null
                ? 'Delivery technische fout'
                : 'Geen wijziging',
            default => $delivery !== null
                ? 'Delivery handmatig beoordelen'
                : 'Handmatig beoordelen',
        };
    }

    private function extractDeliveryToken(string $content): ?string
    {
        if (preg_match(
            '/^X-Topbags-Delivery-Token:\s*([a-f0-9]{64})\s*$/mi',
            $content,
            $match
        ) === 1) {
            return strtolower($match[1]);
        }

        if (preg_match(
            '/newsletter-([a-f0-9]{64})@topbags\.nl/i',
            $content,
            $match
        ) === 1) {
            return strtolower($match[1]);
        }

        return null;
    }

    private function extractOriginalMessageId(string $content): ?string
    {
        if (preg_match_all(
            '/^Message-ID:\s*<?([^>\r\n]+)>?/mi',
            $content,
            $matches
        ) < 1) {
            return null;
        }

        foreach ($matches[1] as $messageId) {
            $messageId = trim((string) $messageId);

            if (
                str_starts_with(
                    strtolower($messageId),
                    'newsletter-'
                )
                && str_ends_with(
                    strtolower($messageId),
                    '@topbags.nl'
                )
            ) {
                return $messageId;
            }
        }

        return null;
    }

    private function extractMessageDate(object $message): \DateTimeImmutable
    {
        try {
            $date = $message->getDate()?->toDate();

            if ($date instanceof \DateTimeInterface) {
                return \DateTimeImmutable::createFromInterface($date);
            }
        } catch (\Throwable) {
            // Gebruik hieronder het huidige tijdstip als fallback.
        }

        return new \DateTimeImmutable();
    }

    private function extractRecipient(string $content): ?string
    {
        $patterns = [
            '/Final-Recipient:\s*(?:rfc822;)?\s*<?([^\s<>;]+@[^\s<>;]+)>?/i',
            '/Original-Recipient:\s*(?:rfc822;)?\s*<?([^\s<>;]+@[^\s<>;]+)>?/i',
            '/following address\(es\) failed:\s*(?:\r?\n)+\s*<?([^\s<>]+@[^\s<>]+)>?/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $match) === 1) {
                return mb_strtolower(trim($match[1]));
            }
        }

        return null;
    }

    private function extractStatus(string $content): ?string
    {
        if (preg_match(
            '/^Status:\s*([245]\.\d+\.\d+)/mi',
            $content,
            $match
        ) !== 1) {
            return null;
        }

        return trim($match[1]);
    }

    private function extractDiagnostic(string $content): ?string
    {
        if (preg_match(
            '/^Diagnostic-Code:\s*(.*(?:\R[ \t]+.*)*)/mi',
            $content,
            $match
        ) !== 1) {
            return null;
        }

        $diagnostic = preg_replace(
            '/\R[ \t]+/',
            ' ',
            $match[1]
        ) ?? $match[1];

        return trim($diagnostic);
    }

    private function classifyBounce(
        ?string $status,
        ?string $diagnostic,
    ): string {
        $status = strtolower(trim($status ?? ''));
        $diagnostic = strtolower(trim($diagnostic ?? ''));

        $technicalPatterns = [
            'smtp authentication is required',
            'authentication required',
            'relay access denied',
            'relay denied',
            'dmarc',
            'spf',
            'sender rejected',
            'sending ip',
            'policy rejection',
        ];

        foreach ($technicalPatterns as $pattern) {
            if (str_contains($diagnostic, $pattern)) {
                return 'technical';
            }
        }

        $softPatterns = [
            'mailbox is full',
            'mailbox full',
            'quota exceeded',
            'over quota',
            'blocks limit exceeded',
            'inode limit exceeded',
            'temporarily unavailable',
            'temporary failure',
            'try again later',
            'too many messages',
            'rate limit',
        ];

        foreach ($softPatterns as $pattern) {
            if (str_contains($diagnostic, $pattern)) {
                return 'soft';
            }
        }

        if (preg_match(
            '/mailbox .* (?:is )?full/i',
            $diagnostic
        ) === 1) {
            return 'soft';
        }

        $hardPatterns = [
            'does not exist',
            'user does not exist',
            'user unknown',
            'unknown user',
            'no such user',
            'no such recipient',
            'invalid recipient',
            'invalid mailbox',
            'mailbox unavailable',
            'mailbox not found',
            'address not found',
            'recipient not found',
            'recipient is not known',
        ];

        foreach ($hardPatterns as $pattern) {
            if (str_contains($diagnostic, $pattern)) {
                return 'hard';
            }
        }

        $hardRegexPatterns = [
            '/mailbox .* unknown/i',
            '/recipient .* not known/i',
            '/recipient .* unknown/i',
            '/account .* does not exist/i',
        ];

        foreach ($hardRegexPatterns as $pattern) {
            if (preg_match($pattern, $diagnostic) === 1) {
                return 'hard';
            }
        }

        if (
            str_starts_with($status, '5.1.0')
            || str_starts_with($status, '5.1.1')
        ) {
            return 'hard';
        }

        if (str_starts_with($status, '4.')) {
            return 'soft';
        }

        return 'review';
    }

    private function truncate(string $value, int $length): string
    {
        $value = preg_replace(
            '/\s+/',
            ' ',
            trim($value)
        ) ?? trim($value);

        if (mb_strlen($value) <= $length) {
            return $value;
        }

        return mb_substr($value, 0, $length - 1) . '…';
    }
}