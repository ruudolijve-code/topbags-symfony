<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Loyalty\Entity\TravelMilesVoucher;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class TravelMilesVoucherCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TravelMilesVoucher::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Travelmiles voucher')
            ->setEntityLabelInPlural('Travelmiles vouchers')
            ->setPageTitle(Crud::PAGE_INDEX, 'Travelmiles vouchers')
            ->setPageTitle(Crud::PAGE_NEW, 'Travelmiles voucher aanmaken')
            ->setPageTitle(Crud::PAGE_EDIT, 'Travelmiles voucher bewerken')
            ->setDefaultSort([
                'createdAt' => 'DESC',
            ])
            ->setSearchFields([
                'code',
                'campaign',
                'member.email',
                'member.firstName',
                'member.lastName',
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
            ->add('member')
            ->add('status')
            ->add('campaign')
            ->add('createdAt')
            ->add('sentAt')
            ->add('redeemedAt')
            ->add('expiresAt');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Voucher');

        yield AssociationField::new('member', 'Travelmiles lid')
            ->setRequired(true)
            ->setHelp('Kies het Travelmiles-lid waarvoor deze voucher is.');

        yield TextField::new('code', 'Code')
            ->setHelp('Wordt automatisch aangemaakt. Deze code moet straks ook als coupon in checkout werken.');

        yield MoneyField::new('amount', 'Waarde')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setHelp('Voor de welkomstactie gebruik je 10.00.');

        yield TextField::new('currency', 'Valuta')
            ->hideOnIndex()
            ->setHelp('Standaard EUR.');

        yield ChoiceField::new('status', 'Status')
            ->setChoices([
                'Aangemaakt' => TravelMilesVoucher::STATUS_CREATED,
                'Verstuurd' => TravelMilesVoucher::STATUS_SENT,
                'Gebruikt' => TravelMilesVoucher::STATUS_REDEEMED,
                'Verlopen' => TravelMilesVoucher::STATUS_EXPIRED,
                'Geannuleerd' => TravelMilesVoucher::STATUS_CANCELLED,
            ]);

        yield TextField::new('campaign', 'Campagne')
            ->setRequired(false)
            ->setHelp('Bijvoorbeeld: Welkomstvoucher, Moederdag 2026 of Verjaardag.');

        yield FormField::addPanel('Statusdatums');

        yield DateTimeField::new('expiresAt', 'Geldig tot')
            ->setRequired(false)
            ->setHelp('Standaard 6 maanden geldig vanaf aanmaken.');

        yield DateTimeField::new('sentAt', 'Verstuurd op')
            ->setRequired(false);

        yield DateTimeField::new('redeemedAt', 'Gebruikt op')
            ->setRequired(false);

        yield DateTimeField::new('cancelledAt', 'Geannuleerd op')
            ->setRequired(false)
            ->hideOnIndex();

        yield FormField::addPanel('Notities');

        yield TextareaField::new('notes', 'Notities')
            ->hideOnIndex()
            ->setRequired(false);

        yield DateTimeField::new('createdAt', 'Aangemaakt op')
            ->setFormat('dd-MM-yyyy HH:mm')
            ->hideOnForm();
    }
}