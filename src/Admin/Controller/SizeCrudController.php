<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Catalog\Entity\Size;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SizeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Size::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Maat')
            ->setEntityLabelInPlural('Maten')
            ->setPageTitle(Crud::PAGE_INDEX, 'Maten')
            ->setPageTitle(Crud::PAGE_NEW, 'Nieuwe maat')
            ->setPageTitle(Crud::PAGE_EDIT, 'Maat bewerken')
            ->setDefaultSort([
                'sortOrder' => 'ASC',
                'name' => 'ASC',
            ])
            ->setSearchFields([
                'name',
                'code',
                'slug',
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield TextField::new('name', 'Maat')
            ->setHelp('Bijvoorbeeld 85 cm, 7,5, S, M, L of XL.');

        yield TextField::new('code', 'Code')
            ->setRequired(false)
            ->setHelp(
                'Technische code, bijvoorbeeld 85, 7.5, S of XL.'
            );

        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name')
            ->hideOnIndex();

        yield IntegerField::new('sortOrder', 'Volgorde')
            ->setHelp(
                'Bepaalt de volgorde van de maten. Bijvoorbeeld 850 voor 85 cm en 750 voor maat 7,5.'
            );

        yield BooleanField::new('isActive', 'Actief');
    }
}