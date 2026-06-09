<?php

declare(strict_types=1);

namespace App\Loyalty\Service;

use App\Marketing\Entity\NewsletterSubscription;
use App\Marketing\Repository\NewsletterSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

final class NewsletterSubscriptionSyncService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NewsletterSubscriptionRepository $subscriptionRepository,
    ) {
    }

    public function syncEmail(string $email, string $source): ?NewsletterSubscription
    {
        $email = mb_strtolower(trim($email));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $existing = $this->subscriptionRepository->findOneBy([
            'email' => $email,
        ]);

        if ($existing instanceof NewsletterSubscription) {
            /*
             * Belangrijk:
             * Iemand die zich heeft uitgeschreven mag niet automatisch opnieuw actief worden.
             */
            if (!$existing->isActive() || $existing->getUnsubscribedAt() !== null) {
                return $existing;
            }

            return $existing;
        }

        $subscription = new NewsletterSubscription();
        $subscription->setEmail($email);
        $subscription->setSource($source);
        $subscription->setIsActive(true);
        $subscription->ensureUnsubscribeToken();

        $this->entityManager->persist($subscription);

        return $subscription;
    }
}