<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Loyalty\Entity\TravelMilesMember;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class TravelMilesMemberCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TravelMilesMember::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Travelmiles lid')
            ->setEntityLabelInPlural('Travelmiles leden')
            ->setPageTitle(Crud::PAGE_INDEX, 'Travelmiles leden')
            ->setPageTitle(Crud::PAGE_NEW, 'Travelmiles lid toevoegen')
            ->setPageTitle(Crud::PAGE_EDIT, 'Travelmiles lid bewerken')
            ->setDefaultSort([
                'createdAt' => 'DESC',
            ]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('isActive')
            ->add('voucherSent')
            ->add('email')
            ->add('createdAt')
            ->add('dateOfBirth');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield EmailField::new('email', 'E-mailadres');

        yield TextField::new('firstName', 'Voornaam');

        yield TextField::new('lastName', 'Achternaam');

        yield DateField::new('dateOfBirth', 'Geboortedatum')
            ->setFormat('dd-MM-yyyy');

        yield BooleanField::new('isActive', 'Actief');

        yield BooleanField::new('voucherSent', 'Voucher verzonden');

        yield TextField::new('source', 'Bron')
            ->hideOnIndex();

        yield TextField::new('street', 'Straat')
            ->hideOnIndex();

        yield TextField::new('houseNumber', 'Huisnummer')
            ->hideOnIndex();

        yield TextField::new('postalCode', 'Postcode')
            ->hideOnIndex();

        yield TextField::new('city', 'Plaats')
            ->hideOnIndex();

        yield CountryField::new('country', 'Land')
            ->hideOnIndex();

        yield DateTimeField::new('consentGivenAt', 'E-mail toestemming gegeven op')
            ->setFormat('dd-MM-yyyy HH:mm')
            ->hideOnForm();

        yield DateTimeField::new('postalMailConsentAt', 'Post toestemming gegeven op')
            ->setFormat('dd-MM-yyyy HH:mm')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'Aangemaakt op')
            ->setFormat('dd-MM-yyyy HH:mm')
            ->hideOnForm();
    }
}