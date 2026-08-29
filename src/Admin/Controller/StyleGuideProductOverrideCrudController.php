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
use EasyCorp\Bundle\EasyAdminBundle\Filter\AssociationFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;

final class StyleGuideProductOverrideCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return StyleGuideProductOverride::class; }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Productuitzondering')->setEntityLabelInPlural('Productuitzonderingen')
            ->setDefaultSort(['styleWorld.position' => 'ASC', 'id' => 'DESC'])
            ->setSearchFields(['product.name', 'product.modelSku', 'styleWorld.name', 'reason']);
    }
    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(AssociationFilter::new('styleWorld'))->add(AssociationFilter::new('product'))
            ->add(NumericFilter::new('scoreAdjustment'))->add(BooleanFilter::new('isActive'));
    }
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('styleWorld', 'Stijlwereld');
        yield AssociationField::new('product', 'Product')->autocomplete();
        yield IntegerField::new('scoreAdjustment', 'Scorecorrectie')->setHelp('Alleen voor uitzonderingen; positief bevoordeelt en negatief verlaagt.');
        yield TextField::new('reason', 'Reden')->setRequired(false);
        yield BooleanField::new('isActive', 'Actief');
    }
}
