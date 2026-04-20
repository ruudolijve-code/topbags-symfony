<?php

namespace App\Admin\Controller;

use App\Shop\Entity\Coupon;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;

class CouponCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Coupon::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Coupon')
            ->setEntityLabelInPlural('Coupons')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields([
                'code',
                'name',
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
            ->add(DateTimeFilter::new('startsAt', 'Startdatum'))
            ->add(DateTimeFilter::new('endsAt', 'Einddatum'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Coupon');

        yield TextField::new('code', 'Code')
            ->setHelp('Bijvoorbeeld SPORTCLUB10 of VVHENGELO15');

        yield TextField::new('name', 'Naam / interne omschrijving');

        yield NumberField::new('discountPercent', 'Korting (%)')
            ->setNumDecimals(2)
            ->setHelp('Voer alleen het percentage in, bijvoorbeeld 10 voor 10% korting.');

        yield BooleanField::new('isActive', 'Actief');

        yield DateTimeField::new('startsAt', 'Startdatum')
            ->setRequired(false);

        yield DateTimeField::new('endsAt', 'Einddatum')
            ->setRequired(false);

        yield NumberField::new('minimumOrderAmount', 'Minimaal bestelbedrag')
            ->setNumDecimals(2)
            ->setRequired(false)
            ->setHelp('Laat leeg als er geen minimum bestelbedrag geldt.');

        yield IntegerField::new('maxRedemptions', 'Maximaal aantal keer te gebruiken')
            ->setRequired(false)
            ->setHelp('Laat leeg voor onbeperkt gebruik.');

        yield IntegerField::new('timesRedeemed', 'Aantal keer gebruikt')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'Aangemaakt op')
            ->hideOnForm();
    }
}