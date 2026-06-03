<?php

namespace App\Admin\Controller;

use App\Marketing\Entity\NewsletterSubscription;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STORE')]
class NewsletterSubscriptionCrudController extends AbstractCrudController
{
    private const SOURCE_CHOICES = [
        'Topbags webshop' => 'topbags_webshop',
        'Holtkamp winkel' => 'holtkamp_store',
        'Handmatig admin' => 'admin_manual',
    ];

    public static function getEntityFqcn(): string
    {
        return NewsletterSubscription::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Nieuwsbriefinschrijving')
            ->setEntityLabelInPlural('Nieuwsbriefinschrijvingen')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields([
                'email',
                'source',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isActive', 'Actief'))
            ->add(
                ChoiceFilter::new('source', 'Ingeschreven via')
                    ->setChoices(self::SOURCE_CHOICES)
            );
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm()
            ->hideOnIndex();

        yield EmailField::new('email', 'E-mailadres')
            ->setRequired(true);

        yield BooleanField::new('isActive', 'Actief');

        yield ChoiceField::new('source', 'Ingeschreven via')
            ->setChoices(self::SOURCE_CHOICES)
            ->setHelp('Gebruik “Holtkamp winkel” voor inschrijvingen die in de winkel zijn gedaan.')
            ->renderAsBadges([
                'topbags_webshop' => 'info',
                'holtkamp_store' => 'success',
                'admin_manual' => 'warning',
            ]);

        yield DateTimeField::new('createdAt', 'Ingeschreven op')
            ->hideOnForm();
    }
}