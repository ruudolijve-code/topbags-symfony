<?php

namespace App\Admin\Controller;

use App\Catalog\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Product')
            ->setEntityLabelInPlural('Producten')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields([
                'modelSku',
                'name',
                'slug',
                'series',
                'luggageType',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Basis');

        yield TextField::new('modelSku', 'Model SKU');

        yield TextField::new('name', 'Naam');

        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name');

        yield AssociationField::new('brand', 'Merk');

        yield TextField::new('series', 'Serie')
            ->setRequired(false)
            ->hideOnIndex();

        yield ChoiceField::new('productContext', 'Context')
            ->setChoices([
                'Koffers & Reistassen' => Product::CONTEXT_SHOP,
                'Damestassen' => Product::CONTEXT_BAGS,
            ])
            ->setRequired(true)
            ->setHelp('Bepaalt of dit product onder reisartikelen of damestassen valt.')
            ->hideOnIndex();

        yield ChoiceField::new('luggageType', 'Bagagetype')
            ->setChoices([
                'Hardcase' => 'hardcase',
                'Softcase' => 'softcase',
                'Duffle' => 'duffle',
                'Backpack' => 'backpack',
            ])
            ->setRequired(false)
            ->setHelp('Alleen invullen voor reisartikelen. Voor damestassen leeg laten.');

        yield BooleanField::new('isActive', 'Actief');

        yield AssociationField::new('material', 'Materiaal')
            ->setRequired(false);

        yield AssociationField::new('categories', 'Categorieën')
            ->setFormTypeOption('by_reference', false)
            ->setFormTypeOption('multiple', true)
            ->renderAsNativeWidget()
            ->hideOnIndex();

        yield TextEditorField::new('description', 'Omschrijving')
            ->setRequired(false)
            ->hideOnIndex();

        yield FormField::addPanel('Afmetingen en inhoud');

        yield NumberField::new('heightCm', 'Hoogte (cm)')
            ->setRequired(false)
            ->hideOnIndex();

        yield NumberField::new('widthCm', 'Breedte (cm)')
            ->setRequired(false)
            ->hideOnIndex();

        yield NumberField::new('depthCm', 'Diepte (cm)')
            ->setRequired(false)
            ->hideOnIndex();

        yield NumberField::new('weightKg', 'Gewicht (kg)')
            ->setRequired(false)
            ->hideOnIndex();

        yield NumberField::new('volumeL', 'Volume (L)')
            ->setRequired(false)
            ->hideOnIndex();

        yield BooleanField::new('expandable', 'Uitbreidbaar')
            ->hideOnIndex();

        yield NumberField::new('expandableVolumeL', 'Extra volume (L)')
            ->setRequired(false)
            ->hideOnIndex();

        yield NumberField::new('expandableDepthCm', 'Extra diepte (cm)')
            ->setRequired(false)
            ->hideOnIndex();

        yield IntegerField::new('wheelsCount', 'Aantal wielen')
            ->setRequired(false)
            ->hideOnIndex();

        yield FormField::addPanel('Eigenschappen');

        yield BooleanField::new('cabinSize', 'Cabin size')
            ->hideOnIndex();

        yield BooleanField::new('underseater', 'Underseater')
            ->hideOnIndex();

        yield BooleanField::new('tsaLock', 'TSA-slot')
            ->hideOnIndex();

        yield ChoiceField::new('closureType', 'Sluiting')
            ->setChoices([
                'Rits' => 'zip',
                'Frame' => 'frame',
                'Klep' => 'flap',
                'Drukknoop' => 'snap',
                'Draaisluiting' => 'twist',
                'Magnetische sluiting' => 'magnetic',
            ])
            ->setRequired(false)
            ->hideOnIndex();

        yield BooleanField::new('laptopCompartment', 'Laptopvak')
            ->hideOnIndex();

        yield NumberField::new('laptopMaxInch', 'Max. laptopformaat (inch)')
            ->setRequired(false)
            ->hideOnIndex();

        yield TextField::new('warrantyYears', 'Garantie')
            ->setRequired(false)
            ->hideOnIndex();
    }
}