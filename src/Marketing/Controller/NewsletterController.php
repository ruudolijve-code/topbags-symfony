<?php

namespace App\Marketing\Controller;

use App\Marketing\Entity\NewsletterSubscription;
use App\Marketing\Repository\NewsletterSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewsletterController extends AbstractController
{
    #[Route('/newsletter/subscribe', name: 'newsletter_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        EntityManagerInterface $em,
        NewsletterSubscriptionRepository $repository
    ): Response {
        $email = mb_strtolower(trim((string) $request->request->get('email', '')));

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