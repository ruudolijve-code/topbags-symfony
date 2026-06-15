<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Marketing\Entity\NewsletterCampaign;
use App\Marketing\Message\SendNewsletterTestMessage;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class NewsletterCampaignBulkTestController extends AbstractController
{
    private const MAX_TEST_RECIPIENTS = 20;

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    #[Route(
        '/admin_dedtwaw/newsletter-campaign/{id}/bulk-test',
        name: 'admin_newsletter_campaign_bulk_test',
        methods: ['GET', 'POST']
    )]
    public function bulkTest(
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
                'admin/newsletter_campaign/bulk_test.html.twig',
                [
                    'campaign' => $campaign,
                    'detailUrl' => $detailUrl,
                    'maximumRecipients' => self::MAX_TEST_RECIPIENTS,
                ]
            );
        }

        $token = (string) $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid(
            'newsletter_bulk_test_' . $campaign->getId(),
            $token
        )) {
            $this->addFlash(
                'danger',
                'De bulktest kon niet worden gestart. Probeer het opnieuw.'
            );

            return $this->redirect($detailUrl);
        }

        $rawEmails = (string) $request->request->get('emails', '');
        $emails = $this->parseEmails($rawEmails);

        if ($emails === []) {
            $this->addFlash(
                'danger',
                'Vul minimaal één geldig testadres in.'
            );

            return $this->redirectToRoute(
                'admin_newsletter_campaign_bulk_test',
                ['id' => $campaign->getId()]
            );
        }

        if (count($emails) > self::MAX_TEST_RECIPIENTS) {
            $this->addFlash(
                'danger',
                sprintf(
                    'Je kunt maximaal %d testadressen tegelijk gebruiken.',
                    self::MAX_TEST_RECIPIENTS
                )
            );

            return $this->redirectToRoute(
                'admin_newsletter_campaign_bulk_test',
                ['id' => $campaign->getId()]
            );
        }

        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash(
                    'danger',
                    sprintf('Het e-mailadres “%s” is niet geldig.', $email)
                );

                return $this->redirectToRoute(
                    'admin_newsletter_campaign_bulk_test',
                    ['id' => $campaign->getId()]
                );
            }
        }

        $campaignId = $campaign->getId();

        if ($campaignId === null) {
            throw new \LogicException(
                'De nieuwsbriefcampagne moet eerst worden opgeslagen.'
            );
        }

        foreach ($emails as $email) {
            $this->messageBus->dispatch(
                new SendNewsletterTestMessage(
                    campaignId: $campaignId,
                    email: $email,
                )
            );
        }

        $this->addFlash(
            'success',
            sprintf(
                '%d testmail%s in de wachtrij geplaatst.',
                count($emails),
                count($emails) === 1 ? '' : 's'
            )
        );

        return $this->redirect($detailUrl);
    }

    /**
     * @return list<string>
     */
    private function parseEmails(string $rawEmails): array
    {
        $parts = preg_split(
            '/[\s,;]+/',
            mb_strtolower(trim($rawEmails)),
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        if (!is_array($parts)) {
            return [];
        }

        $emails = array_map(
            static fn (string $email): string => trim($email),
            $parts
        );

        return array_values(array_unique($emails));
    }
}