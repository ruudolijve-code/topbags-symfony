<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Marketing\Entity\NewsletterCampaign;
use App\Marketing\Entity\NewsletterDelivery;
use App\Marketing\Message\SendNewsletterMessage;
use App\Marketing\Repository\NewsletterSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class NewsletterCampaignSendController extends AbstractController
{
    private const CONFIRMATION_TEXT = 'VERSTUREN';

    public function __construct(
        private readonly NewsletterSubscriptionRepository $subscriptionRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(
        '/admin_dedtwaw/newsletter-campaign/{id}/send',
        name: 'admin_newsletter_campaign_send',
        methods: ['GET', 'POST']
    )]
    public function send(
        NewsletterCampaign $campaign,
        Request $request,
    ): Response {
        $campaignId = $campaign->getId();

        if ($campaignId === null) {
            throw new \LogicException(
                'De nieuwsbriefcampagne moet eerst worden opgeslagen.'
            );
        }

        $detailUrl = $this->adminUrlGenerator
            ->unsetAll()
            ->setController(NewsletterCampaignCrudController::class)
            ->setAction('detail')
            ->setEntityId($campaignId)
            ->generateUrl();

        if (!$campaign->isDraft()) {
            $this->addFlash(
                'danger',
                'Deze nieuwsbrief is al verzonden of wordt momenteel verzonden.'
            );

            return $this->redirect($detailUrl);
        }

        $recipientCount = $this->subscriptionRepository->countActive();

        if (!$request->isMethod('POST')) {
            return $this->render(
                'admin/newsletter_campaign/send.html.twig',
                [
                    'campaign' => $campaign,
                    'detailUrl' => $detailUrl,
                    'recipientCount' => $recipientCount,
                    'confirmationText' => self::CONFIRMATION_TEXT,
                ]
            );
        }

        $token = (string) $request->request->get('_token', '');

        $confirmation = mb_strtoupper(
            trim((string) $request->request->get(
                'confirmation',
                ''
            ))
        );

        if (!$this->isCsrfTokenValid(
            'newsletter_send_' . $campaignId,
            $token
        )) {
            $this->addFlash(
                'danger',
                'De nieuwsbrief kon niet worden ingepland. Probeer het opnieuw.'
            );

            return $this->redirect($detailUrl);
        }

        if ($confirmation !== self::CONFIRMATION_TEXT) {
            $this->addFlash(
                'danger',
                sprintf(
                    'Typ exact “%s” om de verzending te bevestigen.',
                    self::CONFIRMATION_TEXT
                )
            );

            return $this->redirectToRoute(
                'admin_newsletter_campaign_send',
                ['id' => $campaignId]
            );
        }

        /*
         * Haal de actieve ontvangers opnieuw op tijdens de POST.
         * De telling op de bevestigingspagina kan intussen gewijzigd zijn.
         */
        $subscriptions = $this->subscriptionRepository
            ->findActiveForSending();

        $recipientCount = count($subscriptions);

        if ($recipientCount === 0) {
            $this->addFlash(
                'danger',
                'Er zijn geen actieve nieuwsbriefontvangers gevonden.'
            );

            return $this->redirect($detailUrl);
        }

        try {
            /*
             * De campagne, delivery-records en Messenger-berichten worden
             * binnen één databasetransactie ingepland.
             */
            $this->entityManager->wrapInTransaction(
                function () use (
                    $campaign,
                    $subscriptions,
                    $recipientCount
                ): void {
                    if (!$campaign->isDraft()) {
                        throw new \LogicException(
                            'De nieuwsbrief is niet langer een concept.'
                        );
                    }

                    $campaign->markSending($recipientCount);

                    /** @var list<NewsletterDelivery> $deliveries */
                    $deliveries = [];

                    foreach ($subscriptions as $subscription) {
                        $delivery = (new NewsletterDelivery())
                            ->setCampaign($campaign)
                            ->setSubscription($subscription)
                            ->setRecipientEmail(
                                $subscription->getEmail()
                            );

                        $this->entityManager->persist($delivery);

                        $deliveries[] = $delivery;
                    }

                    /*
                     * Eerst flushen zodat iedere delivery een ID krijgt.
                     */
                    $this->entityManager->flush();

                    foreach ($deliveries as $delivery) {
                        $deliveryId = $delivery->getId();

                        if ($deliveryId === null) {
                            throw new \LogicException(
                                'De nieuwsbriefbezorging heeft geen ID gekregen.'
                            );
                        }

                        $this->messageBus->dispatch(
                            new SendNewsletterMessage(
                                deliveryId: $deliveryId,
                            )
                        );
                    }
                }
            );
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Nieuwsbrief kon niet in de wachtrij worden geplaatst.',
                [
                    'campaignId' => $campaignId,
                    'recipientCount' => $recipientCount,
                    'exception' => $exception,
                ]
            );

            $this->addFlash(
                'danger',
                'De nieuwsbrief kon niet worden ingepland. Er zijn geen mails verzonden.'
            );

            return $this->redirect($detailUrl);
        }

        $this->addFlash(
            'success',
            sprintf(
                'De nieuwsbrief is voor %d ontvangers in de wachtrij geplaatst.',
                $recipientCount
            )
        );

        return $this->redirect($detailUrl);
    }
}