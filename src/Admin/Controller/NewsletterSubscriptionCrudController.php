<?php

namespace App\Admin\Controller;

use App\Marketing\Entity\NewsletterSubscription;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

class NewsletterSubscriptionCrudController extends AbstractCrudController
{
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
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW)
            ->disable(Action::EDIT);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isActive', 'Actief'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm()
            ->hideOnIndex();

        yield EmailField::new('email', 'E-mailadres');

        yield BooleanField::new('isActive', 'Actief');

        yield TextField::new('source', 'Bron')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'Ingeschreven op')
            ->hideOnForm();
    }
}