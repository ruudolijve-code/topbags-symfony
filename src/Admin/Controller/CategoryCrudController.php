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
            ->setHelp('Bijvoorbeeld: handbagage, koffers, schoudertassen');

        yield AssociationField::new('parent', 'Hoofdcategorie')
            ->setHelp('Optioneel. Gebruik dit als deze categorie onder een andere categorie valt.');

        yield IntegerField::new('position', 'Menuvolgorde')
            ->setHelp('Lager nummer komt eerder in het menu.');

        yield BooleanField::new('isActive', 'Actief');

        yield BooleanField::new('showInMenu', 'Toon in menu');

       yield TextEditorField::new('description', 'Omschrijving')
            ->hideOnIndex()
            ->setHelp('Deze tekst wordt gebruikt op de categoriepagina.');

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