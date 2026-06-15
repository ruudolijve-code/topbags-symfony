<?php

declare(strict_types=1);

namespace App\Marketing\Service;

use App\Marketing\Entity\NewsletterCampaign;
use App\Marketing\Entity\NewsletterSubscription;
use LogicException;
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
        $emailAddress = trim($subscription->getEmail());
        $token = trim((string) $subscription->getUnsubscribeToken());

        if ($emailAddress === '') {
            throw new LogicException('De nieuwsbriefinschrijving heeft geen e-mailadres.');
        }

        if ($token === '') {
            throw new LogicException(sprintf(
                'Ontbrekende uitschrijftoken voor nieuwsbriefinschrijving %s.',
                $emailAddress
            ));
        }

        $unsubscribeUrl = $this->urlGenerator->generate(
            'newsletter_unsubscribe',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->send(
            campaign: $campaign,
            to: $emailAddress,
            unsubscribeUrl: $unsubscribeUrl,
            isTest: false,
        );
    }

    public function sendTest(
        NewsletterCampaign $campaign,
        string $to,
    ): void {
        $to = mb_strtolower(trim($to));

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new LogicException('Het testadres is geen geldig e-mailadres.');
        }

        $this->send(
            campaign: $campaign,
            to: $to,
            unsubscribeUrl: '#testmail-geen-echte-uitschrijflink',
            isTest: true,
        );
    }

    private function send(
        NewsletterCampaign $campaign,
        string $to,
        string $unsubscribeUrl,
        bool $isTest,
    ): void {
        $subject = trim((string) $campaign->getSubject());

        if ($subject === '') {
            throw new LogicException('De nieuwsbrief heeft geen onderwerpregel.');
        }

        $email = (new TemplatedEmail())
            ->from(new Address('nieuwsbrief@topbags.nl', 'Topbags.nl'))
            ->replyTo(new Address('info@topbags.nl', 'Topbags.nl'))
            ->to(new Address($to))
            ->subject(($isTest ? '[TEST] ' : '') . $subject)
            ->htmlTemplate('email/newsletter.html.twig')
            ->context([
                'campaign' => $campaign,
                'unsubscribeUrl' => $unsubscribeUrl,
                'isTest' => $isTest,
            ]);

        if (!$isTest) {
            $email->getHeaders()->addTextHeader(
                'List-Unsubscribe',
                sprintf('<%s>', $unsubscribeUrl)
            );
        }

        $this->mailer->send($email);
    }
}