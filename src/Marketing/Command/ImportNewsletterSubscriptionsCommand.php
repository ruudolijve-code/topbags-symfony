<?php

declare(strict_types=1);

namespace App\Marketing\Command;

use App\Marketing\Entity\NewsletterSubscription;
use App\Marketing\Repository\NewsletterSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:newsletter:import',
    description: 'Importeert nieuwsbriefinschrijvingen uit een CSV-bestand zonder dubbele e-mailadressen.'
)]
final class ImportNewsletterSubscriptionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NewsletterSubscriptionRepository $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Pad naar CSV-bestand');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = (string) $input->getArgument('file');

        if (!is_file($file) || !is_readable($file)) {
            $output->writeln(sprintf('<error>Bestand niet gevonden of niet leesbaar: %s</error>', $file));

            return Command::FAILURE;
        }

        $handle = fopen($file, 'rb');

        if ($handle === false) {
            $output->writeln('<error>CSV-bestand kon niet worden geopend.</error>');

            return Command::FAILURE;
        }

        $created = 0;
        $skippedExisting = 0;
        $skippedInvalid = 0;
        $rowNumber = 0;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            ++$rowNumber;

            $email = $this->extractEmailFromRow($row);

            if ($email === null) {
                ++$skippedInvalid;
                continue;
            }

            /*
             * Header overslaan.
             */
            if ($rowNumber === 1 && $email === 'email') {
                continue;
            }

            $existing = $this->repository->findOneByEmail($email);

            if ($existing instanceof NewsletterSubscription) {
                /*
                 * Veiligste AVG-keuze:
                 * bestaande adressen niet wijzigen en niet opnieuw activeren.
                 */
                $existing->ensureUnsubscribeToken();

                ++$skippedExisting;
                continue;
            }

            $subscription = new NewsletterSubscription();
            $subscription
                ->setEmail($email)
                ->setIsActive(true)
                ->setSource('import')
                ->ensureUnsubscribeToken();

            $this->em->persist($subscription);
            ++$created;

            if (($created + $skippedExisting + $skippedInvalid) % 100 === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        fclose($handle);

        $this->em->flush();

        $output->writeln('<info>Import klaar.</info>');
        $output->writeln(sprintf('Nieuw toegevoegd: %d', $created));
        $output->writeln(sprintf('Bestaand overgeslagen: %d', $skippedExisting));
        $output->writeln(sprintf('Ongeldig overgeslagen: %d', $skippedInvalid));

        return Command::SUCCESS;
    }

    /**
     * @param array<int, string|null> $row
     */
    private function extractEmailFromRow(array $row): ?string
    {
        foreach ($row as $value) {
            if (!is_string($value)) {
                continue;
            }

            $email = mb_strtolower(trim($value));

            if ($email === '') {
                continue;
            }

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return null;
    }
}