<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Catalog\Entity\MaterialFamily;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class MaterialFamilyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MaterialFamily::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Materiaalfamilie')
            ->setEntityLabelInPlural('Materiaalfamilies')
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Naam');
        yield SlugField::new('slug', 'Slug')->setTargetFieldName('name');
        yield TextEditorField::new('description', 'Omschrijving')->hideOnIndex();
        yield AssociationField::new('materials', 'Materialen')->onlyOnDetail();
    }
}
