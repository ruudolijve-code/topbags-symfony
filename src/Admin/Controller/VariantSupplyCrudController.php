<?php

namespace App\Admin\Controller;

use App\Catalog\Entity\VariantSupply;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class VariantSupplyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return VariantSupply::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Variant supplier override')
            ->setEntityLabelInPlural('Variant supplier overrides')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields([
                'variant.variantSku',
                'supplier.name',
                'supplierSku',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('variant', 'Variant');
        yield AssociationField::new('supplier', 'Leverancier');

        yield TextField::new('supplierSku', 'Supplier SKU')
            ->setRequired(false);

        yield BooleanField::new('isActive', 'Actief');

        yield IntegerField::new('leadTimeMin', 'Levertijd min')
            ->setRequired(false)
            ->setHelp('Leeg laten = supplier default');

        yield IntegerField::new('leadTimeMax', 'Levertijd max')
            ->setRequired(false)
            ->setHelp('Leeg laten = supplier default');

        yield DateTimeField::new('lastSyncedAt', 'Laatst gesynchroniseerd')
            ->hideOnForm()
            ->hideOnIndex();
    }
}