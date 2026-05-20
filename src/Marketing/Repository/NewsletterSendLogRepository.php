<?php

declare(strict_types=1);

namespace App\Marketing\Repository;

use App\Marketing\Entity\NewsletterCampaign;
use App\Marketing\Entity\NewsletterSendLog;
use App\Marketing\Entity\NewsletterSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class NewsletterSendLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsletterSendLog::class);
    }

    public function existsForCampaignAndSubscription(
        NewsletterCampaign $campaign,
        NewsletterSubscription $subscription,
    ): bool {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.campaign = :campaign')
            ->andWhere('l.subscription = :subscription')
            ->setParameter('campaign', $campaign)
            ->setParameter('subscription', $subscription)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}