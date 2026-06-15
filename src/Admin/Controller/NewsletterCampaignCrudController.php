<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Marketing\Entity\NewsletterCampaign;
use App\Marketing\Repository\NewsletterDeliveryRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class NewsletterCampaignCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly NewsletterDeliveryRepository $deliveryRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return NewsletterCampaign::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Nieuwsbrief')
            ->setEntityLabelInPlural('Nieuwsbrieven')
            ->setPageTitle(
                Crud::PAGE_INDEX,
                'Nieuwsbrieven'
            )
            ->setPageTitle(
                Crud::PAGE_NEW,
                'Nieuwe nieuwsbrief'
            )
            ->setPageTitle(
                Crud::PAGE_EDIT,
                static fn (
                    NewsletterCampaign $campaign
                ): string => sprintf(
                    'Nieuwsbrief bewerken: %s',
                    $campaign->getTitle()
                )
            )
            ->setPageTitle(
                Crud::PAGE_DETAIL,
                static fn (
                    NewsletterCampaign $campaign
                ): string => sprintf(
                    'Nieuwsbrief: %s',
                    $campaign->getTitle()
                )
            )
            ->setDefaultSort([
                'createdAt' => 'DESC',
            ])
            ->overrideTemplate(
                'crud/detail',
                'admin/newsletter_campaign/detail.html.twig'
            );
    }

    public function configureResponseParameters(
        KeyValueStore $responseParameters
    ): KeyValueStore {
        if (
            $responseParameters->get('pageName')
            !== Crud::PAGE_DETAIL
        ) {
            return $responseParameters;
        }

        $entityDto = $responseParameters->get('entity');

        if ($entityDto === null) {
            return $responseParameters;
        }

        $campaign = $entityDto->getInstance();

        if (!$campaign instanceof NewsletterCampaign) {
            return $responseParameters;
        }

        $responseParameters->set(
            'deliveryStatistics',
            $this->deliveryRepository
                ->getStatisticsForCampaign($campaign)
        );

        return $responseParameters;
    }

    public function configureActions(Actions $actions): Actions
    {
        $preview = Action::new(
            'previewNewsletter',
            'Preview',
            'fa fa-eye'
        )
            ->linkToUrl(
                function (
                    NewsletterCampaign $campaign
                ): string {
                    return $this->adminUrlGenerator
                        ->unsetAll()
                        ->setRoute(
                            'admin_newsletter_campaign_preview',
                            [
                                'id' => $campaign->getId(),
                            ]
                        )
                        ->generateUrl();
                }
            )
            ->setHtmlAttributes([
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
            ]);

        $testMail = Action::new(
            'testNewsletter',
            'Testmail',
            'fa fa-envelope'
        )
            ->linkToUrl(
                function (
                    NewsletterCampaign $campaign
                ): string {
                    return $this->adminUrlGenerator
                        ->unsetAll()
                        ->setRoute(
                            'admin_newsletter_campaign_test_mail',
                            [
                                'id' => $campaign->getId(),
                            ]
                        )
                        ->generateUrl();
                }
            );

        $bulkTest = Action::new(
            'bulkTestNewsletter',
            'Bulktest',
            'fa fa-users'
        )
            ->linkToUrl(
                function (
                    NewsletterCampaign $campaign
                ): string {
                    return $this->adminUrlGenerator
                        ->unsetAll()
                        ->setRoute(
                            'admin_newsletter_campaign_bulk_test',
                            [
                                'id' => $campaign->getId(),
                            ]
                        )
                        ->generateUrl();
                }
            )
            ->displayIf(
                static fn (
                    NewsletterCampaign $campaign
                ): bool => $campaign->isDraft()
            )
            ->addCssClass('btn btn-warning');

        $sendNewsletter = Action::new(
            'sendNewsletter',
            'Nieuwsbrief versturen',
            'fa fa-paper-plane'
        )
            ->linkToUrl(
                function (
                    NewsletterCampaign $campaign
                ): string {
                    return $this->adminUrlGenerator
                        ->unsetAll()
                        ->setRoute(
                            'admin_newsletter_campaign_send',
                            [
                                'id' => $campaign->getId(),
                            ]
                        )
                        ->generateUrl();
                }
            )
            ->displayIf(
                static fn (
                    NewsletterCampaign $campaign
                ): bool => $campaign->isDraft()
            )
            ->addCssClass('btn btn-danger');

        return $actions
            /*
             * Overzichtspagina
             */
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $preview)
            ->add(Crud::PAGE_INDEX, $testMail)
            ->add(Crud::PAGE_INDEX, $bulkTest)
            ->add(Crud::PAGE_INDEX, $sendNewsletter)

            /*
             * Detailpagina
             */
            ->add(Crud::PAGE_DETAIL, $preview)
            ->add(Crud::PAGE_DETAIL, $testMail)
            ->add(Crud::PAGE_DETAIL, $bulkTest)
            ->add(Crud::PAGE_DETAIL, $sendNewsletter)

            /*
             * Bewerkpagina
             *
             * De bulk- en definitieve verzendknoppen staan hier bewust
             * niet. De nieuwsbrief moet eerst worden opgeslagen.
             */
            ->add(Crud::PAGE_EDIT, $preview)
            ->add(Crud::PAGE_EDIT, $testMail)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)

            /*
             * Alleen conceptnieuwsbrieven mogen worden bewerkt.
             */
            ->update(
                Crud::PAGE_INDEX,
                Action::EDIT,
                static fn (
                    Action $action
                ): Action => $action->displayIf(
                    static fn (
                        NewsletterCampaign $campaign
                    ): bool => $campaign->isDraft()
                )
            )
            ->update(
                Crud::PAGE_DETAIL,
                Action::EDIT,
                static fn (
                    Action $action
                ): Action => $action->displayIf(
                    static fn (
                        NewsletterCampaign $campaign
                    ): bool => $campaign->isDraft()
                )
            )

            /*
             * Campagnes worden bewaard voor historie en statistieken.
             */
            ->disable(Action::DELETE);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('status')
            ->add('createdAt')
            ->add('sentAt');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Inhoud');

        yield TextField::new(
            'title',
            'Interne titel'
        )
            ->setHelp(
                'Alleen zichtbaar in admin, bijvoorbeeld: Voorjaarsactie koffers mei 2026.'
            );

        yield TextField::new(
            'subject',
            'Onderwerpregel'
        )
            ->setHelp(
                'Dit wordt de onderwerpregel van de e-mail.'
            );

        yield TextField::new(
            'preheader',
            'Preheader'
        )
            ->setRequired(false)
            ->setHelp(
                'Korte previewtekst die sommige e-mailclients naast of onder het onderwerp tonen.'
            );

        yield TextareaField::new(
            'htmlBody',
            'HTML inhoud'
        )
            ->onlyOnForms()
            ->setNumOfRows(24)
            ->setHelp(
                'Gebruik eenvoudige HTML met inline styles. Geen Twig-code gebruiken.'
            );

        yield TextField::new(
            'emailPreview',
            'E-mailpreview'
        )
            ->onlyOnDetail()
            ->setTemplatePath(
                'admin/field/newsletter_preview.html.twig'
            );

        yield FormField::addTab('Status & statistieken');

        yield IdField::new('id')
            ->onlyOnDetail();

        yield ChoiceField::new(
            'status',
            'Status'
        )
            ->setChoices([
                'Concept' => NewsletterCampaign::STATUS_DRAFT,
                'Wordt verzonden' => NewsletterCampaign::STATUS_SENDING,
                'Verzonden' => NewsletterCampaign::STATUS_SENT,
            ])
            ->renderAsBadges([
                NewsletterCampaign::STATUS_DRAFT => 'secondary',
                NewsletterCampaign::STATUS_SENDING => 'warning',
                NewsletterCampaign::STATUS_SENT => 'success',
            ])
            ->setFormTypeOption(
                'disabled',
                true
            );

        yield IntegerField::new(
            'recipientCount',
            'Ontvangers'
        )
            ->setFormTypeOption(
                'disabled',
                true
            );

        yield IntegerField::new(
            'sentCount',
            'SMTP geaccepteerd'
        )
            ->setHelp(
                'Aantal berichten dat door de uitgaande mailserver is geaccepteerd.'
            )
            ->setFormTypeOption(
                'disabled',
                true
            );

        yield IntegerField::new(
            'failedCount',
            'Direct mislukt'
        )
            ->setHelp(
                'Aantal berichten dat al tijdens het verzendproces direct is mislukt.'
            )
            ->setFormTypeOption(
                'disabled',
                true
            );

        yield DateTimeField::new(
            'createdAt',
            'Aangemaakt op'
        )
            ->hideOnForm();

        yield DateTimeField::new(
            'sentAt',
            'Verwerking afgerond op'
        )
            ->hideOnForm();
    }
}