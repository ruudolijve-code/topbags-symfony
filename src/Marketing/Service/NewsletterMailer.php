<?php

declare(strict_types=1);

namespace App\Marketing\Service;

use App\Marketing\Entity\NewsletterCampaign;
use App\Marketing\Entity\NewsletterDelivery;
use LogicException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class NewsletterMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * Verstuurt een echte nieuwsbriefbezorging.
     *
     * De geretourneerde Message-ID wordt ook opgeslagen in
     * NewsletterDelivery, zodat een bounce later aan deze verzending
     * kan worden gekoppeld.
     */
    public function sendDelivery(
        NewsletterDelivery $delivery,
    ): string {
        $campaign = $delivery->getCampaign();
        $subscription = $delivery->getSubscription();

        if (!$campaign instanceof NewsletterCampaign) {
            throw new LogicException(
                'De nieuwsbriefbezorging heeft geen campagne.'
            );
        }

        if ($subscription === null) {
            throw new LogicException(
                'De nieuwsbriefbezorging heeft geen inschrijving.'
            );
        }

        $emailAddress = mb_strtolower(
            trim($delivery->getRecipientEmail())
        );

        if (
            $emailAddress === ''
            || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)
        ) {
            throw new LogicException(
                'De nieuwsbriefbezorging heeft geen geldig e-mailadres.'
            );
        }

        $unsubscribeToken = trim(
            (string) $subscription->getUnsubscribeToken()
        );

        if ($unsubscribeToken === '') {
            throw new LogicException(sprintf(
                'Ontbrekende uitschrijftoken voor %s.',
                $emailAddress
            ));
        }

        $campaignId = $campaign->getId();

        if ($campaignId === null) {
            throw new LogicException(
                'De nieuwsbriefcampagne heeft geen ID.'
            );
        }

        $unsubscribeUrl = $this->urlGenerator->generate(
            'newsletter_unsubscribe',
            ['token' => $unsubscribeToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $messageId = sprintf(
            'newsletter-%s@topbags.nl',
            $delivery->getDeliveryToken()
        );

        $this->send(
            campaign: $campaign,
            to: $emailAddress,
            unsubscribeUrl: $unsubscribeUrl,
            isTest: false,
            messageId: $messageId,
            campaignId: $campaignId,
            deliveryToken: $delivery->getDeliveryToken(),
        );

        return $messageId;
    }

    /**
     * Verstuurt een testmail zonder delivery-record.
     */
    public function sendTest(
        NewsletterCampaign $campaign,
        string $to,
    ): void {
        $to = mb_strtolower(trim($to));

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new LogicException(
                'Het testadres is geen geldig e-mailadres.'
            );
        }

        $this->send(
            campaign: $campaign,
            to: $to,
            unsubscribeUrl: '#testmail-geen-echte-uitschrijflink',
            isTest: true,
            messageId: sprintf(
                'newsletter-test-%s@topbags.nl',
                bin2hex(random_bytes(16))
            ),
        );
    }

    private function send(
        NewsletterCampaign $campaign,
        string $to,
        string $unsubscribeUrl,
        bool $isTest,
        string $messageId,
        ?int $campaignId = null,
        ?string $deliveryToken = null,
    ): void {
        $subject = trim((string) $campaign->getSubject());

        if ($subject === '') {
            throw new LogicException(
                'De nieuwsbrief heeft geen onderwerpregel.'
            );
        }

        $email = (new TemplatedEmail())
            ->from(new Address(
                'nieuwsbrief@topbags.nl',
                'Topbags.nl'
            ))
            ->replyTo(new Address(
                'info@topbags.nl',
                'Topbags.nl'
            ))
            ->to(new Address($to))
            ->subject(($isTest ? '[TEST] ' : '') . $subject)
            ->htmlTemplate('email/newsletter.html.twig')
            ->context([
                'campaign' => $campaign,
                'unsubscribeUrl' => $unsubscribeUrl,
                'isTest' => $isTest,
            ]);

        $headers = $email->getHeaders();

        $headers->addIdHeader(
            'Message-ID',
            $messageId
        );

        $email->embedFromPath(
            $this->projectDir
                . '/public/images/social/facebook.png',
            'newsletter-facebook',
            'image/png'
        );

        $email->embedFromPath(
            $this->projectDir
                . '/public/images/social/instagram.png',
            'newsletter-instagram',
            'image/png'
        );

        $email->embedFromPath(
            $this->projectDir
                . '/public/images/social/youtube.png',
            'newsletter-youtube',
            'image/png'
        );

        if (!$isTest) {
            if ($campaignId === null || $deliveryToken === null) {
                throw new LogicException(
                    'Campagne-ID of delivery-token ontbreekt.'
                );
            }

            $headers->addTextHeader(
                'List-Unsubscribe',
                sprintf('<%s>', $unsubscribeUrl)
            );

            $headers->addTextHeader(
                'X-Topbags-Campaign-ID',
                (string) $campaignId
            );

            $headers->addTextHeader(
                'X-Topbags-Delivery-Token',
                $deliveryToken
            );
        }

        $this->mailer->send($email);
    }
}