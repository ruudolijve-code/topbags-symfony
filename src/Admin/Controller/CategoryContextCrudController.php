<?php

namespace App\Admin\Controller;

use App\Catalog\Entity\CategoryContext;
use App\Catalog\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class CategoryContextCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CategoryContext::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Categorie-context')
            ->setEntityLabelInPlural('Categorie-contexten');
    }

    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('context', 'Context')
            ->setChoices([
                'Shop / koffers' => Product::CONTEXT_SHOP,
                'Bags / tassen' => Product::CONTEXT_BAGS,
            ]);

        yield IntegerField::new('position', 'Volgorde binnen context')
            ->setHelp('Lager nummer komt eerder in dit menu.');
    }
}