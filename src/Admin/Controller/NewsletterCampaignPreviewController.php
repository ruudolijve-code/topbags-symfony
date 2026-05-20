<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Marketing\Entity\NewsletterCampaign;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class NewsletterCampaignPreviewController extends AbstractController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    #[Route('/admin_dedtwaw/newsletter-campaign/{id}/preview', name: 'admin_newsletter_campaign_preview', methods: ['GET'])]
    public function preview(NewsletterCampaign $campaign): Response
    {
        $detailUrl = $this->adminUrlGenerator
            ->unsetAll()
            ->setController(NewsletterCampaignCrudController::class)
            ->setAction('detail')
            ->setEntityId($campaign->getId())
            ->generateUrl();

        return $this->render('admin/newsletter_campaign/preview.html.twig', [
            'campaign' => $campaign,
            'detailUrl' => $detailUrl,
        ]);
    }

    #[Route('/admin_dedtwaw/newsletter-campaign/{id}/preview-frame', name: 'admin_newsletter_campaign_preview_frame', methods: ['GET'])]
    public function previewFrame(NewsletterCampaign $campaign): Response
    {
        return $this->render('email/newsletter.html.twig', [
            'campaign' => $campaign,
            'unsubscribeUrl' => '#unsubscribe-preview',
        ]);
    }
}