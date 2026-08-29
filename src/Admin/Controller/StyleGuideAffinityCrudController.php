<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\StyleGuide\Entity\StyleGuideAffinity;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\AssociationFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;

final class StyleGuideAffinityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return StyleGuideAffinity::class; }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Stijlaffiniteit')->setEntityLabelInPlural('Stijlaffiniteiten')
            ->setDefaultSort(['styleWorld.position' => 'ASC', 'position' => 'ASC', 'id' => 'ASC'])
            ->setSearchFields(['styleWorld.name', 'brand.name', 'material.name', 'color.name', 'category.name', 'colorFamily', 'reason']);
    }
    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(AssociationFilter::new('styleWorld'))->add(AssociationFilter::new('brand'))
            ->add(AssociationFilter::new('material'))->add(AssociationFilter::new('color'))->add(AssociationFilter::new('category'))
            ->add(TextFilter::new('colorFamily'))->add(NumericFilter::new('score'))->add(BooleanFilter::new('isActive'));
    }
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('styleWorld', 'Stijlwereld')->setRequired(true);
        yield AssociationField::new('brand', 'Merk')->setRequired(false);
        yield AssociationField::new('material', 'Materiaal')->setRequired(false);
        yield AssociationField::new('color', 'Kleur')->setRequired(false);
        yield AssociationField::new('category', 'Categorie')->setRequired(false);
        yield TextField::new('colorFamily', 'Kleurfamilie')->setRequired(false)->setHelp('Vul exact één doel in. Gebruik dezelfde waarde als Color.family, bijvoorbeeld bruin.');
        yield IntegerField::new('score', 'Score')->setHelp('Sterke positieve affiniteiten liggen doorgaans tussen +15 en +40.');
        yield TextField::new('reason', 'Reden')->setRequired(false)->setHelp('Wordt als matchreden aan de klant getoond bij een positieve match.');
        yield IntegerField::new('position', 'Volgorde');
        yield BooleanField::new('isActive', 'Actief');
    }
}
