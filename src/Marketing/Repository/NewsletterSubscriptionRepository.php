<?php

declare(strict_types=1);

namespace App\Marketing\Repository;

use App\Marketing\Entity\NewsletterSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsletterSubscription>
 */
final class NewsletterSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            NewsletterSubscription::class
        );
    }

    public function findOneByEmail(
        string $email
    ): ?NewsletterSubscription {
        $email = mb_strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        /** @var NewsletterSubscription|null $subscription */
        $subscription = $this->findOneBy([
            'email' => $email,
        ]);

        return $subscription;
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('subscription')
            ->select('COUNT(subscription.id)')
            ->andWhere('subscription.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Haalt alle actieve inschrijvingen op die voor een nieuwe
     * nieuwsbriefcampagne mogen worden ingepland.
     *
     * @return list<NewsletterSubscription>
     */
    public function findActiveForSending(): array
    {
        /** @var list<NewsletterSubscription> $subscriptions */
        $subscriptions = $this->createQueryBuilder('subscription')
            ->andWhere('subscription.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('subscription.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $subscriptions;
    }

    /**
     * Behouden voor bestaande processen die alleen de ID's nodig hebben.
     *
     * @return list<int>
     */
    public function findActiveIds(): array
    {
        /** @var list<array{id: int|string}> $rows */
        $rows = $this->createQueryBuilder('subscription')
            ->select('subscription.id AS id')
            ->andWhere('subscription.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('subscription.id', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows
        );
    }
}