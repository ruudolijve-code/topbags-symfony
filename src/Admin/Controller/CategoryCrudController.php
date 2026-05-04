<?php

namespace App\Admin\Controller;

use App\Catalog\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Categorie')
            ->setEntityLabelInPlural('Categorieën')
            ->setPageTitle(Crud::PAGE_INDEX, 'Categorieën / menu')
            ->setPageTitle(Crud::PAGE_NEW, 'Categorie toevoegen')
            ->setPageTitle(Crud::PAGE_EDIT, 'Categorie bewerken')
            ->setDefaultSort([
                'position' => 'ASC',
                'name' => 'ASC',
            ]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('isActive')
            ->add('showInMenu')
            ->add('parent');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield TextField::new('name', 'Naam');

        yield TextField::new('menuLabel', 'Menulabel')
            ->setHelp('Optioneel. Laat leeg om de gewone categorienaam te gebruiken.');

        yield TextField::new('slug', 'Slug')
            ->setHelp('Bijvoorbeeld: koffers, handbagage, schoudertassen.');

        yield AssociationField::new('parent', 'Hoofdcategorie')
            ->setHelp('Optioneel. Gebruik dit als deze categorie onder een andere categorie valt.');

        yield IntegerField::new('position', 'Algemene volgorde')
            ->setHelp('Algemene sortering. Lager nummer komt eerder.');

        yield BooleanField::new('isActive', 'Actief');

        yield BooleanField::new('showInMenu', 'Toon in menu');

        yield BooleanField::new('shopContext', 'Toon in shop/koffers')
            ->setHelp('Gebruik deze categorie binnen de shop/koffer-context.');

        yield IntegerField::new('shopMenuPosition', 'Volgorde shop/koffers')
            ->setHelp('Lager nummer komt eerder in het shop-menu.');

        yield BooleanField::new('bagsContext', 'Toon in bags/tassen')
            ->setHelp('Gebruik deze categorie binnen de bags/tassen-context.');

        yield IntegerField::new('bagsMenuPosition', 'Volgorde bags/tassen')
            ->setHelp('Lager nummer komt eerder in het bags-menu.');

        yield TextEditorField::new('introDescription', 'Intro tekst boven producten')
            ->hideOnIndex()
            ->setHelp('Korte tekst boven het productgrid. Houd dit compact: ongeveer 80–150 woorden.');

        yield TextEditorField::new('seoDescription', 'SEO tekst onder producten')
            ->hideOnIndex()
            ->setHelp('Uitgebreide SEO-/advies tekst onder het productgrid.');

        yield TextEditorField::new('description', 'Oude omschrijving')
            ->hideOnIndex()
            ->setHelp('Tijdelijke fallback. Gebruik dit veld alleen nog voor bestaande oude teksten.');

        yield BooleanField::new('allowsPersonal', 'Personal item')
            ->hideOnIndex();

        yield BooleanField::new('allowsCabin', 'Cabin bagage')
            ->hideOnIndex();

        yield BooleanField::new('allowsHold', 'Ruimbagage')
            ->hideOnIndex();

        yield BooleanField::new('transportPlane', 'Vliegtuig')
            ->hideOnIndex();

        yield BooleanField::new('transportCar', 'Auto')
            ->hideOnIndex();

        yield BooleanField::new('transportTrain', 'Trein')
            ->hideOnIndex();

        yield BooleanField::new('transportBus', 'Bus')
            ->hideOnIndex();
    }
}