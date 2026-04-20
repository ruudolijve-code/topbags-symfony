<?php

namespace App\Admin\Controller;

use App\Catalog\Entity\Supplier;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SupplierCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Supplier::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Leverancier')
            ->setEntityLabelInPlural('Leveranciers')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields([
                'name',
                'slug',
                'parentCompany',
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

        yield TextField::new('parentCompany', 'Parent company')
            ->setRequired(false)
            ->hideOnIndex();

        yield IntegerField::new('defaultLeadTimeMin', 'Levertijd min')
            ->setHelp('Aantal werkdagen');

        yield IntegerField::new('defaultLeadTimeMax', 'Levertijd max')
            ->setHelp('Aantal werkdagen');

        yield BooleanField::new('isActive', 'Actief');
    }
}