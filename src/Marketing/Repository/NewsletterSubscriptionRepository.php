<?php

declare(strict_types=1);

namespace App\Marketing\Repository;

use App\Marketing\Entity\NewsletterSubscription;
use App\Marketing\Entity\NewsletterCampaign;
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

    /**
     * Berekent historische bouncecijfers voor een campagne die vóór de
     * invoering van NewsletterDelivery is verzonden.
     *
     * @return array{
     *     hardBounce: int,
     *     softBounce: int,
     *     totalBounce: int,
     *     withoutHardOrSoftBounce: int,
     *     bounceRate: float,
     *     from: ?\DateTimeImmutable,
     *     until: ?\DateTimeImmutable
     * }
     */
    public function getLegacyBounceStatisticsForCampaign(
        NewsletterCampaign $campaign,
    ): array {
        $sentAt = $campaign->getSentAt();

        if ($sentAt === null) {
            return [
                'hardBounce' => 0,
                'softBounce' => 0,
                'totalBounce' => 0,
                'withoutHardOrSoftBounce' => 0,
                'bounceRate' => 0.0,
                'from' => null,
                'until' => null,
            ];
        }

        /*
        * Bounces komen meestal binnen enkele minuten of dagen terug.
        * De kleine marge vóór sentAt vangt tijdzone- en klokverschillen op.
        */
        $from = $sentAt->modify('-15 minutes');
        $until = $sentAt->modify('+7 days');

        /** @var array<string, int|string|null> $result */
        $result = $this->createQueryBuilder('subscription')
            ->select(
                'SUM(
                    CASE
                        WHEN subscription.lastBounceType = :hard
                        THEN 1
                        ELSE 0
                    END
                ) AS hardBounce'
            )
            ->addSelect(
                'SUM(
                    CASE
                        WHEN subscription.lastBounceType = :soft
                        THEN 1
                        ELSE 0
                    END
                ) AS softBounce'
            )
            ->andWhere('subscription.lastBouncedAt >= :from')
            ->andWhere('subscription.lastBouncedAt <= :until')
            ->setParameter(
                'hard',
                NewsletterSubscription::BOUNCE_TYPE_HARD
            )
            ->setParameter(
                'soft',
                NewsletterSubscription::BOUNCE_TYPE_SOFT
            )
            ->setParameter('from', $from)
            ->setParameter('until', $until)
            ->getQuery()
            ->getSingleResult();

        $hardBounce = (int) ($result['hardBounce'] ?? 0);
        $softBounce = (int) ($result['softBounce'] ?? 0);
        $totalBounce = $hardBounce + $softBounce;

        /*
        * sentCount betekent hier: door SMTP geaccepteerd.
        */
        $smtpAccepted = $campaign->getSentCount();

        $withoutHardOrSoftBounce = max(
            0,
            $smtpAccepted - $totalBounce
        );

        return [
            'hardBounce' => $hardBounce,
            'softBounce' => $softBounce,
            'totalBounce' => $totalBounce,
            'withoutHardOrSoftBounce' => $withoutHardOrSoftBounce,
            'bounceRate' => $smtpAccepted > 0
                ? round(($totalBounce / $smtpAccepted) * 100, 1)
                : 0.0,
            'from' => $from,
            'until' => $until,
        ];
    }
}