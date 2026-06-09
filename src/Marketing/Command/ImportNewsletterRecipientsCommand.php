<?php

declare(strict_types=1);

namespace App\Marketing\Command;

use App\Marketing\Entity\NewsletterSubscription;
use App\Marketing\Repository\NewsletterSubscriptionRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:newsletter:import-recipients',
    description: 'Importeer Travelmilesleden en bestelklanten naar newsletter_subscription.'
)]
final class ImportNewsletterRecipientsCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityManagerInterface $em,
        private readonly NewsletterSubscriptionRepository $subscriptionRepository,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Toon wat er zou gebeuren, zonder wijzigingen op te slaan.'
            )
            ->addOption(
                'orders-only',
                null,
                InputOption::VALUE_NONE,
                'Importeer alleen e-mailadressen uit bestellingen.'
            )
            ->addOption(
                'travelmiles-only',
                null,
                InputOption::VALUE_NONE,
                'Importeer alleen e-mailadressen uit actieve Travelmilesleden.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool) $input->getOption('dry-run');
        $ordersOnly = (bool) $input->getOption('orders-only');
        $travelmilesOnly = (bool) $input->getOption('travelmiles-only');

        if ($ordersOnly && $travelmilesOnly) {
            $output->writeln('<error>Gebruik niet tegelijk --orders-only en --travelmiles-only.</error>');

            return Command::FAILURE;
        }

        $recipients = [];

        if (!$travelmilesOnly) {
            foreach ($this->getOrderCustomerEmails() as $email) {
                $recipients[] = [
                    'email' => $email,
                    'source' => NewsletterSubscription::SOURCE_CUSTOMER_ORDER,
                ];
            }
        }

        if (!$ordersOnly) {
            foreach ($this->getTravelmilesMemberEmails() as $email) {
                $recipients[] = [
                    'email' => $email,
                    'source' => NewsletterSubscription::SOURCE_TRAVELMILES_MEMBER,
                ];
            }
        }

        $seen = [];
        $imported = 0;
        $skippedDuplicateInImport = 0;
        $skippedExistingActive = 0;
        $skippedUnsubscribed = 0;
        $skippedInvalid = 0;

        foreach ($recipients as $recipient) {
            $email = mb_strtolower(trim((string) $recipient['email']));
            $source = (string) $recipient['source'];

            if ($email === '') {
                $skippedInvalid++;
                continue;
            }

            if (isset($seen[$email])) {
                $skippedDuplicateInImport++;
                continue;
            }

            $seen[$email] = true;

            if (!$this->isValidEmail($email)) {
                $skippedInvalid++;
                continue;
            }

            $existing = $this->subscriptionRepository->findOneBy([
                'email' => $email,
            ]);

            if ($existing instanceof NewsletterSubscription) {
                if (!$existing->isActive() || $existing->getUnsubscribedAt() !== null) {
                    $skippedUnsubscribed++;
                    continue;
                }

                $skippedExistingActive++;
                continue;
            }

            $subscription = new NewsletterSubscription();
            $subscription->setEmail($email);
            $subscription->setSource($source);
            $subscription->setIsActive(true);
            $subscription->ensureUnsubscribeToken();

            if (!$dryRun) {
                $this->em->persist($subscription);
            }

            $imported++;
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        $output->writeln($dryRun ? '<comment>DRY RUN: er is niets opgeslagen.</comment>' : '<info>Import opgeslagen.</info>');
        $output->writeln(sprintf('Unieke adressen bekeken: %d', count($seen)));
        $output->writeln(sprintf('Nieuw geïmporteerd: %d', $imported));
        $output->writeln(sprintf('Overgeslagen: dubbel in import: %d', $skippedDuplicateInImport));
        $output->writeln(sprintf('Overgeslagen: bestaat al actief: %d', $skippedExistingActive));
        $output->writeln(sprintf('Overgeslagen: eerder uitgeschreven: %d', $skippedUnsubscribed));
        $output->writeln(sprintf('Overgeslagen: ongeldig e-mailadres: %d', $skippedInvalid));

        return Command::SUCCESS;
    }

    /**
     * @return iterable<string>
     */
    private function getOrderCustomerEmails(): iterable
    {
        return $this->connection->fetchFirstColumn("
            SELECT DISTINCT LOWER(TRIM(customer_email)) AS email
            FROM shop_order
            WHERE customer_email IS NOT NULL
              AND TRIM(customer_email) <> ''
        ");
    }

    /**
     * @return iterable<string>
     */
    private function getTravelmilesMemberEmails(): iterable
    {
        return $this->connection->fetchFirstColumn("
            SELECT DISTINCT LOWER(TRIM(email)) AS email
            FROM travel_miles_member
            WHERE email IS NOT NULL
              AND TRIM(email) <> ''
              AND is_active = 1
        ");
    }

    private function isValidEmail(string $email): bool
    {
        $violations = $this->validator->validate($email, [
            new Assert\NotBlank(),
            new Assert\Email(),
        ]);

        return count($violations) === 0;
    }
}