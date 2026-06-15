<?php

declare(strict_types=1);

namespace App\Command;

use App\Marketing\Entity\NewsletterSubscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webklex\PHPIMAP\ClientManager;

#[AsCommand(
    name: 'app:newsletter:process-bounces',
    description: 'Leest de bounce-mailbox en toont welke nieuwsbriefinschrijvingen geraakt worden.',
)]
final class ProcessNewsletterBouncesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

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

            $messages = $folder
                ->messages()
                ->all()
                ->get();

            $rows = [];
            $hardCount = 0;
            $softCount = 0;
            $technicalCount = 0;
            $reviewCount = 0;
            $notFoundCount = 0;
            $nonBounceCount = 0;

            foreach ($messages as $message) {
                $rawSubject = (string) $message->getSubject();

                $subject = iconv_mime_decode(
                    $rawSubject,
                    ICONV_MIME_DECODE_CONTINUE_ON_ERROR,
                    'UTF-8'
                );

                $textBody = (string) ($message->getTextBody() ?? '');
                $rawBody = (string) $message->getRawBody();
                $content = $textBody . "\n" . $rawBody;

                $recipient = $this->extractRecipient($content);
                $status = $this->extractStatus($content);
                $diagnostic = $this->extractDiagnostic($content);

                if ($recipient === null) {
                    ++$nonBounceCount;

                    continue;
                }

                $classification = $this->classifyBounce(
                    $status,
                    $diagnostic
                );

                switch ($classification) {
                    case 'hard':
                        ++$hardCount;
                        break;

                    case 'soft':
                        ++$softCount;
                        break;

                    case 'technical':
                        ++$technicalCount;
                        break;

                    default:
                        ++$reviewCount;
                        break;
                }

                /** @var NewsletterSubscription|null $subscription */
                $subscription = $this->entityManager
                    ->getRepository(NewsletterSubscription::class)
                    ->findOneBy([
                        'email' => $recipient,
                    ]);

                if ($subscription === null) {
                    ++$notFoundCount;
                    $databaseStatus = 'Niet gevonden';
                } else {
                    $databaseStatus = $subscription->isActive()
                        ? 'Actief'
                        : 'Al inactief';
                }

                $proposedAction = match ($classification) {
                    'hard' => $subscription?->isActive()
                        ? 'Zou deactiveren'
                        : 'Geen wijziging',
                    'soft' => 'Actief laten',
                    'technical' => 'Geen wijziging',
                    default => 'Handmatig beoordelen',
                };

                $rows[] = [
                    $recipient,
                    $status ?? '-',
                    $classification,
                    $databaseStatus,
                    $proposedAction,
                    $this->truncate(
                        $diagnostic ?? ($subject ?: $rawSubject),
                        80
                    ),
                ];
            }

            $io->title('Nieuwsbriefbounces — veilige controle');

            $io->table(
                [
                    'E-mailadres',
                    'Status',
                    'Classificatie',
                    'Database',
                    'Voorgestelde actie',
                    'Reden',
                ],
                $rows
            );

            $io->definitionList(
                ['Totaal berichten' => count($messages)],
                ['Geen bounceadres gevonden' => $nonBounceCount],
                ['Hard bounces' => $hardCount],
                ['Soft bounces' => $softCount],
                ['Technische fouten' => $technicalCount],
                ['Handmatig beoordelen' => $reviewCount],
                ['Niet in database gevonden' => $notFoundCount],
            );

            $io->note(
                'Er zijn geen databasewijzigingen uitgevoerd en berichten zijn niet verwijderd.'
            );

            $client->disconnect();

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $io->error(sprintf(
                'Verwerking mislukt: %s',
                $exception->getMessage()
            ));

            return Command::FAILURE;
        }
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
                return strtolower(trim($match[1]));
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

        /*
        * Technische verzendproblemen.
        * Deze zeggen niets over de geldigheid van de ontvanger.
        */
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

        /*
        * Tijdelijke afleverproblemen.
        */
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

        if (preg_match('/mailbox .* (?:is )?full/i', $diagnostic) === 1) {
            return 'soft';
        }

        /*
        * Permanente ontvangerfouten.
        */
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

        /*
        * Providerteksten waarbij het adres tussen woorden staat.
        */
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

        /*
        * Specifieke DSN-codes voor een onbekende ontvanger.
        */
        if (
            str_starts_with($status, '5.1.0')
            || str_starts_with($status, '5.1.1')
        ) {
            return 'hard';
        }

        if (str_starts_with($status, '4.')) {
            return 'soft';
        }

        /*
        * Meldingen als 5.4.1 "Access denied" blijven bewust review.
        * Dat kan een blokkade zijn in plaats van een ongeldig adres.
        */
        return 'review';
    }

    private function truncate(string $value, int $length): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        if (mb_strlen($value) <= $length) {
            return $value;
        }

        return mb_substr($value, 0, $length - 1) . '…';
    }
}