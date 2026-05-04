<?php

namespace App\Admin\Controller;

use App\Catalog\Entity\Material;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MaterialCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Material::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Materiaal')
            ->setEntityLabelInPlural('Materialen')
            ->setPageTitle(Crud::PAGE_INDEX, 'Materialen')
            ->setPageTitle(Crud::PAGE_NEW, 'Materiaal toevoegen')
            ->setPageTitle(Crud::PAGE_EDIT, 'Materiaal bewerken')
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield TextField::new('name', 'Naam');

        yield TextField::new('slug', 'Slug')
            ->setHelp('Bijvoorbeeld: polycarbonaat, leer, polyester');

        yield NumberField::new('density', 'Dichtheid')
            ->setHelp('Optioneel. Bijvoorbeeld gewicht/dichtheid als je dit later voor advies wilt gebruiken.')
            ->setNumDecimals(3)
            ->hideOnIndex();

        yield BooleanField::new('isRigid', 'Hard materiaal');

        yield BooleanField::new('isFlexible', 'Flexibel materiaal');

        yield IntegerField::new('sustainabilityScore', 'Duurzaamheidsscore')
            ->setHelp('Optioneel. Bijvoorbeeld 1 t/m 10.')
            ->hideOnIndex();

        yield TextField::new('notes', 'Notities')
            ->hideOnIndex();
    }
}