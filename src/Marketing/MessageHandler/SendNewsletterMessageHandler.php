<?php

declare(strict_types=1);

namespace App\Marketing\MessageHandler;

use App\Marketing\Entity\NewsletterCampaign;
use App\Marketing\Entity\NewsletterDelivery;
use App\Marketing\Message\SendNewsletterMessage;
use App\Marketing\Repository\NewsletterDeliveryRepository;
use App\Marketing\Service\NewsletterMailer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendNewsletterMessageHandler
{
    public function __construct(
        private NewsletterDeliveryRepository $deliveryRepository,
        private NewsletterMailer $newsletterMailer,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendNewsletterMessage $message): void
    {
        $delivery = $this->deliveryRepository->find(
            $message->deliveryId
        );

        if (!$delivery instanceof NewsletterDelivery) {
            $this->logger->error(
                'Nieuwsbriefbezorging niet gevonden.',
                [
                    'deliveryId' => $message->deliveryId,
                ]
            );

            return;
        }

        /*
         * Voorkom dat een reeds verwerkt Messenger-bericht opnieuw
         * wordt verstuurd of opnieuw meetelt in de campagnestatistiek.
         */
        if (
            $delivery->getStatus()
            !== NewsletterDelivery::STATUS_QUEUED
        ) {
            $this->logger->warning(
                'Nieuwsbriefbezorging is al verwerkt.',
                [
                    'deliveryId' => $message->deliveryId,
                    'status' => $delivery->getStatus(),
                ]
            );

            return;
        }

        $campaign = $delivery->getCampaign();

        if (!$campaign instanceof NewsletterCampaign) {
            $this->logger->error(
                'Campagne ontbreekt bij nieuwsbriefbezorging.',
                [
                    'deliveryId' => $message->deliveryId,
                ]
            );

            return;
        }

        /*
         * Alleen deliveries van een actief verzendende campagne
         * mogen worden verwerkt.
         */
        if (!$campaign->isSending()) {
            $this->logger->warning(
                'Nieuwsbriefbezorging overgeslagen omdat de campagne niet wordt verzonden.',
                [
                    'deliveryId' => $message->deliveryId,
                    'campaignId' => $campaign->getId(),
                    'campaignStatus' => $campaign->getStatus(),
                ]
            );

            return;
        }

        $subscription = $delivery->getSubscription();

        /*
         * De inschrijving kan sinds het inplannen verwijderd of
         * gedeactiveerd zijn.
         */
        if ($subscription === null || !$subscription->isActive()) {
            $reason = 'Nieuwsbriefinschrijving bestaat niet meer of is niet actief.';

            $delivery->markDirectFailed($reason);
            $campaign->incrementFailedCount();

            $this->finishCampaignWhenComplete($campaign);
            $this->entityManager->flush();

            $this->logger->warning(
                'Nieuwsbriefontvanger bestaat niet meer of is niet actief.',
                [
                    'deliveryId' => $message->deliveryId,
                    'campaignId' => $campaign->getId(),
                    'email' => $delivery->getRecipientEmail(),
                ]
            );

            return;
        }

        try {
            /*
             * sendDelivery() verstuurt de mail en retourneert het
             * Message-ID dat ook in NewsletterDelivery wordt opgeslagen.
             */
            $messageId = $this->newsletterMailer->sendDelivery(
                $delivery
            );

            /*
             * SMTP geaccepteerd betekent dat de uitgaande mailserver
             * het bericht heeft aangenomen. Een latere bounce kan de
             * deliverystatus nog wijzigen.
             */
            $delivery->markSmtpAccepted($messageId);
            $campaign->incrementSentCount();
        } catch (\Throwable $exception) {
            $reason = trim($exception->getMessage());

            if ($reason === '') {
                $reason = $exception::class;
            }

            $delivery->markDirectFailed($reason);
            $campaign->incrementFailedCount();

            $this->logger->error(
                'Nieuwsbrief kon niet worden verstuurd.',
                [
                    'deliveryId' => $message->deliveryId,
                    'campaignId' => $campaign->getId(),
                    'email' => $delivery->getRecipientEmail(),
                    'exception' => $exception,
                ]
            );
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