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
}