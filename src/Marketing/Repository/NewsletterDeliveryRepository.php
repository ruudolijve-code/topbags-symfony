<?php

declare(strict_types=1);

namespace App\Marketing\Repository;

use App\Marketing\Entity\NewsletterDelivery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsletterDelivery>
 */
final class NewsletterDeliveryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsletterDelivery::class);
    }

    public function findOneByDeliveryToken(
        string $deliveryToken
    ): ?NewsletterDelivery {
        /** @var NewsletterDelivery|null $delivery */
        $delivery = $this->findOneBy([
            'deliveryToken' => trim($deliveryToken),
        ]);

        return $delivery;
    }
}