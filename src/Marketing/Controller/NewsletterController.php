<?php

declare(strict_types=1);

namespace App\Marketing\Controller;

use App\Marketing\Entity\NewsletterSubscription;
use App\Marketing\Repository\NewsletterSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class NewsletterController extends AbstractController
{
    private const CSRF_TOKEN_ID = 'newsletter_subscribe';
    private const MIN_SECONDS_BEFORE_SUBMIT = 2;

    #[Route('/newsletter/subscribe', name: 'newsletter_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        EntityManagerInterface $em,
        NewsletterSubscriptionRepository $repository,
        RateLimiterFactory $newsletterSubscribeLimiter,
    ): Response {
        $email = mb_strtolower(trim((string) $request->request->get('email', '')));
        $token = (string) $request->request->get('_token', '');
        $honeypot = trim((string) $request->request->get('website', ''));
        $startedAt = (int) $request->request->get('form_started_at', 0);

        $limiterKey = sprintf(
            '%s:%s',
            $request->getClientIp() ?? 'unknown',
            $email !== '' ? hash('sha256', $email) : 'empty'
        );

        $limit = $newsletterSubscribeLimiter
            ->create($limiterKey)
            ->consume(1);

        if (!$limit->isAccepted()) {
            $this->addFlash('newsletter_error', 'Je hebt te vaak geprobeerd je in te schrijven. Probeer het later opnieuw.');

            return $this->redirectBack($request);
        }

        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $token)) {
            $this->addFlash('newsletter_error', 'De inschrijving kon niet worden verwerkt. Probeer het opnieuw.');

            return $this->redirectBack($request);
        }

        /*
         * Honeypot:
         * Echte bezoekers zien/vullen dit veld niet in.
         * Bots vullen vaak automatisch alle velden in.
         */
        if ($honeypot !== '') {
            return $this->redirectBack($request);
        }

        /*
         * Tijdcontrole:
         * Een formulier dat direct binnen 1 à 2 seconden wordt verzonden,
         * is meestal een bot.
         */
        if ($startedAt > 0 && (time() - $startedAt) < self::MIN_SECONDS_BEFORE_SUBMIT) {
            return $this->redirectBack($request);
        }

        if ($email === '') {
            $this->addFlash('newsletter_error', 'Vul je e-mailadres in.');

            return $this->redirectBack($request);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('newsletter_error', 'Vul een geldig e-mailadres in.');

            return $this->redirectBack($request);
        }

        $existing = $repository->findOneByEmail($email);

        if ($existing instanceof NewsletterSubscription) {
            if (!$existing->isActive()) {
                $existing->setIsActive(true);
                $existing->setSource('footer');
                $em->flush();
            }

            $this->addFlash('newsletter_success', 'Dit e-mailadres is al ingeschreven.');

            return $this->redirectBack($request);
        }

        $subscription = new NewsletterSubscription();
        $subscription
            ->setEmail($email)
            ->setIsActive(true)
            ->setSource('footer');

        $em->persist($subscription);
        $em->flush();

        $this->addFlash('newsletter_success', 'Bedankt! Je bent ingeschreven voor de nieuwsbrief.');

        return $this->redirectBack($request);
    }

    private function redirectBack(Request $request): Response
    {
        $referer = $request->headers->get('referer');

        if (is_string($referer) && $referer !== '') {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('home');
    }
}