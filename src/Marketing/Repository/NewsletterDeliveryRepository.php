<?php

declare(strict_types=1);

namespace App\Marketing\Repository;

use App\Marketing\Entity\NewsletterCampaign;
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
        $deliveryToken = trim($deliveryToken);

        if ($deliveryToken === '') {
            return null;
        }

        /** @var NewsletterDelivery|null $delivery */
        $delivery = $this->findOneBy([
            'deliveryToken' => $deliveryToken,
        ]);

        return $delivery;
    }

    public function findOneByMessageId(
        string $messageId
    ): ?NewsletterDelivery {
        $messageId = trim($messageId, " \t\n\r\0\x0B<>");

        if ($messageId === '') {
            return null;
        }

        /** @var NewsletterDelivery|null $delivery */
        $delivery = $this->findOneBy([
            'messageId' => $messageId,
        ]);

        return $delivery;
    }

    public function findLatestAcceptedForRecipient(
        string $recipientEmail,
        ?\DateTimeImmutable $before = null,
    ): ?NewsletterDelivery {
        $recipientEmail = mb_strtolower(trim($recipientEmail));

        if ($recipientEmail === '') {
            return null;
        }

        $queryBuilder = $this->createQueryBuilder('delivery')
            ->andWhere('delivery.recipientEmail = :recipientEmail')
            ->andWhere('delivery.status = :status')
            ->setParameter('recipientEmail', $recipientEmail)
            ->setParameter(
                'status',
                NewsletterDelivery::STATUS_SMTP_ACCEPTED
            )
            ->orderBy('delivery.smtpAcceptedAt', 'DESC')
            ->addOrderBy('delivery.id', 'DESC')
            ->setMaxResults(1);

        if ($before !== null) {
            $queryBuilder
                ->andWhere('delivery.smtpAcceptedAt <= :before')
                ->setParameter('before', $before);
        }

        /** @var NewsletterDelivery|null $delivery */
        $delivery = $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();

        return $delivery;
    }

    /**
     * @return array{
     *     total: int,
     *     queued: int,
     *     smtpAccepted: int,
     *     likelyDelivered: int,
     *     directFailed: int,
     *     hardBounce: int,
     *     softBounce: int,
     *     technicalFailure: int,
     *     review: int,
     *     deliveryRate: float
     * }
     */
    public function getStatisticsForCampaign(
        NewsletterCampaign $campaign
    ): array {
        /** @var array<string, int|string|null> $result */
        $result = $this->createQueryBuilder('delivery')
            ->select('COUNT(delivery.id) AS total')
            ->addSelect(
                'SUM(CASE WHEN delivery.status = :queued THEN 1 ELSE 0 END) AS queued'
            )
            ->addSelect(
                'SUM(CASE WHEN delivery.smtpAcceptedAt IS NOT NULL THEN 1 ELSE 0 END) AS smtpAccepted'
            )
            ->addSelect(
                'SUM(CASE WHEN delivery.status = :smtpAcceptedStatus THEN 1 ELSE 0 END) AS likelyDelivered'
            )
            ->addSelect(
                'SUM(CASE WHEN delivery.status = :directFailed THEN 1 ELSE 0 END) AS directFailed'
            )
            ->addSelect(
                'SUM(CASE WHEN delivery.status = :hardBounce THEN 1 ELSE 0 END) AS hardBounce'
            )
            ->addSelect(
                'SUM(CASE WHEN delivery.status = :softBounce THEN 1 ELSE 0 END) AS softBounce'
            )
            ->addSelect(
                'SUM(CASE WHEN delivery.status = :technicalFailure THEN 1 ELSE 0 END) AS technicalFailure'
            )
            ->addSelect(
                'SUM(CASE WHEN delivery.status = :review THEN 1 ELSE 0 END) AS review'
            )
            ->andWhere('delivery.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->setParameter(
                'queued',
                NewsletterDelivery::STATUS_QUEUED
            )
            ->setParameter(
                'smtpAcceptedStatus',
                NewsletterDelivery::STATUS_SMTP_ACCEPTED
            )
            ->setParameter(
                'directFailed',
                NewsletterDelivery::STATUS_DIRECT_FAILED
            )
            ->setParameter(
                'hardBounce',
                NewsletterDelivery::STATUS_HARD_BOUNCE
            )
            ->setParameter(
                'softBounce',
                NewsletterDelivery::STATUS_SOFT_BOUNCE
            )
            ->setParameter(
                'technicalFailure',
                NewsletterDelivery::STATUS_TECHNICAL_FAILURE
            )
            ->setParameter(
                'review',
                NewsletterDelivery::STATUS_REVIEW
            )
            ->getQuery()
            ->getSingleResult();

        $total = (int) ($result['total'] ?? 0);
        $likelyDelivered = (int) ($result['likelyDelivered'] ?? 0);

        return [
            'total' => $total,
            'queued' => (int) ($result['queued'] ?? 0),
            'smtpAccepted' => (int) ($result['smtpAccepted'] ?? 0),
            'likelyDelivered' => $likelyDelivered,
            'directFailed' => (int) ($result['directFailed'] ?? 0),
            'hardBounce' => (int) ($result['hardBounce'] ?? 0),
            'softBounce' => (int) ($result['softBounce'] ?? 0),
            'technicalFailure' => (int) (
                $result['technicalFailure'] ?? 0
            ),
            'review' => (int) ($result['review'] ?? 0),
            'deliveryRate' => $total > 0
                ? round(($likelyDelivered / $total) * 100, 1)
                : 0.0,
        ];
    }
}