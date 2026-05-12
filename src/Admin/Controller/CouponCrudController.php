<?php

namespace App\Admin\Controller;

use App\Shop\Entity\Coupon;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
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
            ->add(ChoiceFilter::new('discountType', 'Kortingstype')->setChoices([
                'Percentage' => Coupon::TYPE_PERCENTAGE,
                'Vast bedrag' => Coupon::TYPE_FIXED_AMOUNT,
            ]))
            ->add(DateTimeFilter::new('startsAt', 'Startdatum'))
            ->add(DateTimeFilter::new('endsAt', 'Einddatum'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Coupon');

        yield TextField::new('code', 'Code')
            ->setHelp('Bijvoorbeeld SPORTCLUB10, VVHENGELO15 of TM-WELKOM10.');

        yield TextField::new('name', 'Naam / interne omschrijving');

        yield ChoiceField::new('discountType', 'Kortingstype')
            ->setChoices([
                'Percentagekorting' => Coupon::TYPE_PERCENTAGE,
                'Vast bedrag' => Coupon::TYPE_FIXED_AMOUNT,
            ])
            ->setHelp('Gebruik “Vast bedrag” voor Travelmiles-vouchers zoals €10 tegoed.');

        yield NumberField::new('discountPercent', 'Korting (%)')
            ->setNumDecimals(2)
            ->setHelp('Alleen gebruiken bij percentagekorting. Zet op 0 bij vaste bedragen.');

        yield MoneyField::new('discountAmount', 'Korting bedrag')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setRequired(false)
            ->setHelp('Alleen gebruiken bij vaste korting. Bijvoorbeeld 10.00 voor €10 Travelmiles tegoed.');

        yield BooleanField::new('isActive', 'Actief');

        yield DateTimeField::new('startsAt', 'Startdatum')
            ->setRequired(false);

        yield DateTimeField::new('endsAt', 'Einddatum')
            ->setRequired(false);

        yield MoneyField::new('minimumOrderAmount', 'Minimaal bestelbedrag')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setRequired(false)
            ->setHelp('Laat leeg als er geen minimum bestelbedrag geldt.');

        yield IntegerField::new('maxRedemptions', 'Maximaal aantal keer te gebruiken')
            ->setRequired(false)
            ->setHelp('Laat leeg voor onbeperkt gebruik. Voor persoonlijke Travelmiles-vouchers meestal 1.');

        yield IntegerField::new('timesRedeemed', 'Aantal keer gebruikt')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'Aangemaakt op')
            ->hideOnForm();
    }
}