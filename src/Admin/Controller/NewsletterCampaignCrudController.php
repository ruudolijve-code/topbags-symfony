<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Marketing\Entity\NewsletterCampaign;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
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
            ->setPageTitle(Crud::PAGE_INDEX, 'Nieuwsbrieven')
            ->setPageTitle(Crud::PAGE_NEW, 'Nieuwe nieuwsbrief')
            ->setPageTitle(
                Crud::PAGE_EDIT,
                fn (NewsletterCampaign $campaign) => sprintf('Nieuwsbrief bewerken: %s', $campaign->getTitle())
            )
            ->setPageTitle(
                Crud::PAGE_DETAIL,
                fn (NewsletterCampaign $campaign) => sprintf('Nieuwsbrief: %s', $campaign->getTitle())
            )
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

   public function configureActions(Actions $actions): Actions
    {
        $preview = Action::new('previewNewsletter', 'Preview', 'fa fa-eye')
            ->linkToUrl(function (NewsletterCampaign $campaign): string {
                return $this->adminUrlGenerator
                    ->unsetAll()
                    ->setRoute('admin_newsletter_campaign_preview', [
                        'id' => $campaign->getId(),
                    ])
                    ->generateUrl();
            })
            ->setHtmlAttributes([
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
            ]);

        $testMail = Action::new('testNewsletter', 'Testmail', 'fa fa-paper-plane')
            ->linkToUrl(function (NewsletterCampaign $campaign): string {
                return $this->adminUrlGenerator
                    ->unsetAll()
                    ->setRoute('admin_newsletter_campaign_test_mail', [
                        'id' => $campaign->getId(),
                    ])
                    ->generateUrl();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $preview)
            ->add(Crud::PAGE_INDEX, $testMail)
            ->add(Crud::PAGE_DETAIL, $preview)
            ->add(Crud::PAGE_DETAIL, $testMail)
            ->add(Crud::PAGE_EDIT, $preview)
            ->add(Crud::PAGE_EDIT, $testMail)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
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

        yield TextField::new('title', 'Interne titel')
            ->setHelp('Alleen zichtbaar in admin, bijvoorbeeld: Voorjaarsactie koffers mei 2026.');

        yield TextField::new('subject', 'Onderwerpregel')
            ->setHelp('Dit wordt de onderwerpregel van de e-mail.');

        yield TextField::new('preheader', 'Preheader')
            ->setRequired(false)
            ->setHelp('Korte previewtekst die sommige e-mailclients naast of onder het onderwerp tonen.');

        yield TextareaField::new('htmlBody', 'HTML inhoud')
            ->onlyOnForms()
            ->setNumOfRows(24)
            ->setHelp('Gebruik eenvoudige HTML met inline styles. Geen Twig-code gebruiken.');

        yield TextField::new('emailPreview', 'E-mailpreview')
            ->onlyOnDetail()
            ->formatValue(function ($value, NewsletterCampaign $campaign): string {
                if ($campaign->getId() === null) {
                    return '';
                }

                return sprintf(
                    '<iframe src="/admin_dedtwaw/newsletter-campaign/%d/preview-frame" style="width:100%%; height:1000px; border:1px solid #d1d5db; border-radius:12px; background:#ffffff; display:block;"></iframe>',
                    $campaign->getId()
                );
            })
            ->renderAsHtml();

        yield FormField::addTab('Status & statistieken');

        yield IdField::new('id')
            ->onlyOnDetail();

        yield ChoiceField::new('status', 'Status')
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
            ->setFormTypeOption('disabled', true);

        yield IntegerField::new('recipientCount', 'Ontvangers')
            ->setFormTypeOption('disabled', true);

        yield IntegerField::new('sentCount', 'Verzonden')
            ->setFormTypeOption('disabled', true);

        yield IntegerField::new('failedCount', 'Mislukt')
            ->setFormTypeOption('disabled', true);

        yield DateTimeField::new('createdAt', 'Aangemaakt op')
            ->hideOnForm();

        yield DateTimeField::new('sentAt', 'Verzonden op')
            ->hideOnForm();
    }
}