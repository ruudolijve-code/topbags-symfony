<?php

declare(strict_types=1);

namespace App\Marketing\MessageHandler;

use App\Marketing\Entity\NewsletterCampaign;
use App\Marketing\Entity\NewsletterSubscription;
use App\Marketing\Message\SendNewsletterMessage;
use App\Marketing\Repository\NewsletterCampaignRepository;
use App\Marketing\Repository\NewsletterSubscriptionRepository;
use App\Marketing\Service\NewsletterMailer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendNewsletterMessageHandler
{
    public function __construct(
        private NewsletterCampaignRepository $campaignRepository,
        private NewsletterSubscriptionRepository $subscriptionRepository,
        private NewsletterMailer $newsletterMailer,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendNewsletterMessage $message): void
    {
        $campaign = $this->campaignRepository->find($message->campaignId);

        if (!$campaign instanceof NewsletterCampaign) {
            $this->logger->error('Nieuwsbriefcampagne niet gevonden.', [
                'campaignId' => $message->campaignId,
                'subscriptionId' => $message->subscriptionId,
            ]);

            return;
        }

        /*
         * Verwerk alleen berichten van een campagne die daadwerkelijk
         * de status "sending" heeft.
         */
        if (!$campaign->isSending()) {
            $this->logger->warning('Nieuwsbriefbericht overgeslagen omdat de campagne niet wordt verzonden.', [
                'campaignId' => $message->campaignId,
                'subscriptionId' => $message->subscriptionId,
                'status' => $campaign->getStatus(),
            ]);

            return;
        }

        $subscription = $this->subscriptionRepository->find(
            $message->subscriptionId
        );

        if (
            !$subscription instanceof NewsletterSubscription
            || !$subscription->isActive()
        ) {
            $campaign->incrementFailedCount();

            $this->finishCampaignWhenComplete($campaign);
            $this->entityManager->flush();

            $this->logger->warning('Nieuwsbriefontvanger bestaat niet meer of is niet actief.', [
                'campaignId' => $message->campaignId,
                'subscriptionId' => $message->subscriptionId,
            ]);

            return;
        }

        try {
            $this->newsletterMailer->sendToSubscription(
                $campaign,
                $subscription
            );

            $campaign->incrementSentCount();
        } catch (\Throwable $exception) {
            $campaign->incrementFailedCount();

            $this->logger->error('Nieuwsbrief kon niet worden verstuurd.', [
                'campaignId' => $message->campaignId,
                'subscriptionId' => $message->subscriptionId,
                'email' => $subscription->getEmail(),
                'exception' => $exception,
            ]);
        }

        $this->finishCampaignWhenComplete($campaign);
        $this->entityManager->flush();
    }

    private function finishCampaignWhenComplete(
        NewsletterCampaign $campaign,
    ): void {
        $processedCount =
            $campaign->getSentCount()
            + $campaign->getFailedCount();

        if (
            $campaign->getRecipientCount() > 0
            && $processedCount >= $campaign->getRecipientCount()
        ) {
            $campaign->markSent();
        }
    }
}