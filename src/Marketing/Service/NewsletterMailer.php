<?php

declare(strict_types=1);

namespace App\Marketing\Service;

use App\Marketing\Entity\NewsletterCampaign;
use App\Marketing\Entity\NewsletterSubscription;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class NewsletterMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function sendToSubscription(
        NewsletterCampaign $campaign,
        NewsletterSubscription $subscription,
    ): void {
        $token = $subscription->getUnsubscribeToken();

        $unsubscribeUrl = $token !== null && $token !== ''
            ? $this->urlGenerator->generate(
                'newsletter_unsubscribe',
                ['token' => $token],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            : '#unsubscribe-missing-token';

        $this->send(
            campaign: $campaign,
            to: $subscription->getEmail(),
            unsubscribeUrl: $unsubscribeUrl,
        );
    }

    public function sendTest(
        NewsletterCampaign $campaign,
        string $to,
    ): void {
        $this->send(
            campaign: $campaign,
            to: $to,
            unsubscribeUrl: '#testmail-geen-echte-uitschrijflink',
        );
    }

    private function send(
        NewsletterCampaign $campaign,
        string $to,
        string $unsubscribeUrl,
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address('nieuwsbrief@topbags.nl', 'Topbags.nl'))
            ->replyTo(new Address('info@topbags.nl', 'Topbags.nl'))
            ->to($to)
            ->subject('[TEST] ' . $campaign->getSubject())
            ->htmlTemplate('email/newsletter.html.twig')
            ->context([
                'campaign' => $campaign,
                'unsubscribeUrl' => $unsubscribeUrl,
            ]);

        $this->mailer->send($email);
    }
}