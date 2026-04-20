<?php

namespace App\Marketing\Repository;

use App\Marketing\Entity\NewsletterSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NewsletterSubscriptionRepository extends ServiceEntityRepository
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
}