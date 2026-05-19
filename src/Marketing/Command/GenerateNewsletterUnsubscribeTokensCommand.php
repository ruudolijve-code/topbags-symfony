<?php

declare(strict_types=1);

namespace App\Marketing\Command;

use App\Marketing\Entity\NewsletterSubscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:newsletter:generate-unsubscribe-tokens',
    description: 'Genereert uitschrijftokens voor nieuwsbriefinschrijvingen zonder token.'
)]
final class GenerateNewsletterUnsubscribeTokensCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repository = $this->em->getRepository(NewsletterSubscription::class);

        /** @var NewsletterSubscription[] $subscriptions */
        $subscriptions = $repository->createQueryBuilder('s')
            ->andWhere('s.unsubscribeToken IS NULL OR s.unsubscribeToken = :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->getResult();

        $count = 0;

        foreach ($subscriptions as $subscription) {
            $subscription->ensureUnsubscribeToken();
            ++$count;

            if ($count % 50 === 0) {
                $this->em->flush();
            }
        }

        $this->em->flush();

        $output->writeln(sprintf(
            '<info>%d uitschrijftokens gegenereerd.</info>',
            $count
        ));

        return Command::SUCCESS;
    }
}