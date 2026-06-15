<?php

declare(strict_types=1);

namespace App\Marketing\MessageHandler;

use App\Marketing\Entity\NewsletterCampaign;
use App\Marketing\Message\SendNewsletterTestMessage;
use App\Marketing\Repository\NewsletterCampaignRepository;
use App\Marketing\Service\NewsletterMailer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendNewsletterTestMessageHandler
{
    public function __construct(
        private NewsletterCampaignRepository $campaignRepository,
        private NewsletterMailer $newsletterMailer,
    ) {
    }

    public function __invoke(SendNewsletterTestMessage $message): void
    {
        $campaign = $this->campaignRepository->find($message->campaignId);

        if (!$campaign instanceof NewsletterCampaign) {
            throw new \RuntimeException(sprintf(
                'Nieuwsbriefcampagne %d bestaat niet.',
                $message->campaignId
            ));
        }

        $email = mb_strtolower(trim($message->email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(sprintf(
                'Ongeldig testadres: %s',
                $message->email
            ));
        }

        $this->newsletterMailer->sendTest($campaign, $email);
    }
}