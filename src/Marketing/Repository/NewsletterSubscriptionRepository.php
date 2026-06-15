<?php

declare(strict_types=1);

namespace App\Marketing\Repository;

use App\Marketing\Entity\NewsletterSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class NewsletterSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsletterSubscription::class);
    }

    public function findOneByEmail(string $email): ?NewsletterSubscription
    {
        return $this->findOneBy([
            'email' => mb_strtolower(trim($email)),
        ]);
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
     * @return list<int>
     */
    public function findActiveIds(): array
    {
        $rows = $this->createQueryBuilder('subscription')
            ->select('subscription.id')
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