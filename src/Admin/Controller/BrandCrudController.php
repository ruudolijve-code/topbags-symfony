<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Catalog\Entity\Brand;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
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
            ->setSearchFields([
                'name',
                'slug',
            ])
            ->setDefaultSort([
                'name' => 'ASC',
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

        yield BooleanField::new(
            'isActive',
            'Actief',
        );

        yield IntegerField::new(
            'brandPositioning',
            'Merkpositionering',
        )
            ->setHelp(
                'Basispositionering van het merk voor de Style Guide, van 0 tot 100. '
                . 'Dit is niet de uiteindelijke productscore. De Style Guide combineert '
                . 'de merkpositionering met onder andere materiaal, prijs en een eventuele '
                . 'productoverride.'
            );

        yield AssociationField::new(
            'defaultSupplier',
            'Standaard leverancier',
        )
            ->setRequired(false);

        yield TextField::new(
            'logo',
            'Logo',
        )
            ->hideOnIndex();

        yield TextEditorField::new(
            'description',
            'Omschrijving',
        )
            ->hideOnIndex();
    }
}