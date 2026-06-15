<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Marketing\Entity\NewsletterCampaign;
use App\Marketing\Service\NewsletterMailer;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class NewsletterCampaignTestMailController extends AbstractController
{
    public function __construct(
        private readonly NewsletterMailer $newsletterMailer,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    #[Route(
        '/admin_dedtwaw/newsletter-campaign/{id}/test-mail',
        name: 'admin_newsletter_campaign_test_mail',
        methods: ['GET', 'POST']
    )]
    public function testMail(
        NewsletterCampaign $campaign,
        Request $request,
    ): Response {
        $detailUrl = $this->adminUrlGenerator
            ->unsetAll()
            ->setController(NewsletterCampaignCrudController::class)
            ->setAction('detail')
            ->setEntityId($campaign->getId())
            ->generateUrl();

        if (!$request->isMethod('POST')) {
            return $this->render(
                'admin/newsletter_campaign/test_mail.html.twig',
                [
                    'campaign' => $campaign,
                    'detailUrl' => $detailUrl,
                ]
            );
        }

        $email = mb_strtolower(
            trim((string) $request->request->get('email', ''))
        );

        $token = (string) $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid(
            'newsletter_test_mail_' . $campaign->getId(),
            $token
        )) {
            $this->addFlash(
                'danger',
                'De testmail kon niet worden verstuurd. Probeer het opnieuw.'
            );

            return $this->redirect($detailUrl);
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash(
                'danger',
                'Vul een geldig test e-mailadres in.'
            );

            return $this->redirectToRoute(
                'admin_newsletter_campaign_test_mail',
                [
                    'id' => $campaign->getId(),
                ]
            );
        }

        try {
            $this->newsletterMailer->sendTest($campaign, $email);
        } catch (TransportExceptionInterface) {
            $this->addFlash(
                'danger',
                'De testmail kon niet worden verstuurd. Controleer de mailconfiguratie en probeer het opnieuw.'
            );

            return $this->redirectToRoute(
                'admin_newsletter_campaign_test_mail',
                [
                    'id' => $campaign->getId(),
                ]
            );
        }

        $this->addFlash(
            'success',
            sprintf('Testmail verstuurd naar %s.', $email)
        );

        return $this->redirect($detailUrl);
    }
}
