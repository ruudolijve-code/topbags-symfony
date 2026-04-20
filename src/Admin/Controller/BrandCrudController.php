<?php

namespace App\Admin\Controller;

use App\Catalog\Entity\Brand;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BrandCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Brand::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Merk')
            ->setEntityLabelInPlural('Merken')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields([
                'name',
                'slug',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Naam');

        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name');

        yield BooleanField::new('isActive', 'Actief');

        yield AssociationField::new('defaultSupplier', 'Standaard leverancier')
            ->setRequired(false);

        yield TextField::new('logo', 'Logo')
            ->hideOnIndex();

        yield TextEditorField::new('description', 'Omschrijving')
            ->hideOnIndex();
    }
}