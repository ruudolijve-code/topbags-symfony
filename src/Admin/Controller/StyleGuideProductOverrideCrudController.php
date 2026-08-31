<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\StyleGuide\Entity\StyleGuideProductOverride;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;

final class StyleGuideProductOverrideCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return StyleGuideProductOverride::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Productuitzondering')
            ->setEntityLabelInPlural('Productuitzonderingen')
            ->setDefaultSort([
                'styleWorld.position' => 'ASC',
                'id' => 'DESC',
            ])
            ->setSearchFields([
                'product.name',
                'product.modelSku',
                'styleWorld.name',
                'reason',
            ]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('styleWorld')
            ->add('product')
            ->add(NumericFilter::new('scoreAdjustment', 'Scorecorrectie'))
            ->add(BooleanFilter::new('isActive', 'Actief'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield AssociationField::new('styleWorld', 'Stijlwereld');

        yield AssociationField::new('product', 'Product')
            ->autocomplete();

        yield IntegerField::new('scoreAdjustment', 'Scorecorrectie')
            ->setHelp(
                'Alleen voor uitzonderingen; een positieve waarde bevoordeelt het product en een negatieve waarde verlaagt de score.'
            );

        yield TextField::new('reason', 'Reden')
            ->setRequired(false)
            ->setHelp(
                'Optionele toelichting waarom dit product binnen deze stijlwereld een afwijkende score krijgt.'
            );

        yield BooleanField::new('isActive', 'Actief');
    }
}