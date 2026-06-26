<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Magazine\Entity\MagazineArticle;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;

final class MagazineArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MagazineArticle::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Magazineartikel')
            ->setEntityLabelInPlural('Magazineartikelen')
            ->setPageTitle(Crud::PAGE_INDEX, 'Magazineartikelen')
            ->setPageTitle(Crud::PAGE_NEW, 'Nieuw magazineartikel')
            ->setPageTitle(Crud::PAGE_EDIT, 'Magazineartikel bewerken')
            ->setDefaultSort(['publishedAt' => 'DESC', 'id' => 'DESC'])
            ->setSearchFields([
                'title',
                'slug',
                'seoTitle',
                'seoDescription',
                'excerpt',
                'category',
                'relatedBrandSlug',
                'relatedCategorySlug',
            ]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('isPublished')
            ->add('category')
            ->add('publishedAt')
            ->add('relatedBrandSlug')
            ->add('relatedCategorySlug');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield BooleanField::new('isPublished', 'Gepubliceerd');

        yield BooleanField::new('isFeatured', 'Uitgelicht')
            ->setHelp('Toon dit artikel als groot uitgelicht artikel op de magazine-overzichtspagina.');

        yield TextField::new('title', 'Titel')
            ->setColumns(8);

        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('title')
            ->setUnlockConfirmationMessage(
                'De slug wordt normaal automatisch gemaakt op basis van de titel. Weet je zeker dat je deze handmatig wilt aanpassen?'
            )
            ->hideOnIndex();

        yield ChoiceField::new('category', 'Categorie')
            ->setChoices([
                'Koffers' => 'Koffers',
                'Handbagage' => 'Handbagage',
                'Reistassen' => 'Reistassen',
                'Rugzakken' => 'Rugzakken',
                'Tassen' => 'Tassen',
                'Leren tassen' => 'Leren tassen',
                'Onderhoud' => 'Onderhoud',
                'Vliegreizen' => 'Vliegreizen',
                'Reistips' => 'Reistips',
                'Nieuws' => 'Nieuws',
            ])
            ->renderExpanded(false)
            ->setColumns(4);

        yield TextareaField::new('excerpt', 'Korte intro')
            ->setHelp('Korte samenvatting voor de overzichtspagina en intro van het artikel.')
            ->hideOnIndex()
            ->setColumns(12);

        yield TextEditorField::new('content', 'Artikeltekst')
            ->setHelp('Gebruik koppen, alinea’s en links. Productblokken voegen we later dynamisch toe.')
            ->hideOnIndex()
            ->setColumns(12);

        yield TextField::new('seoTitle', 'SEO titel')
            ->setHelp('Laat leeg om de gewone titel te gebruiken.')
            ->hideOnIndex()
            ->setColumns(6);

        yield TextareaField::new('seoDescription', 'SEO omschrijving')
            ->setHelp('Gebruik ongeveer 140-160 tekens.')
            ->hideOnIndex()
            ->setColumns(6);

        yield TextField::new('heroImage', 'Hero afbeelding')
            ->setHelp('Optioneel pad, bijvoorbeeld /media/magazine/tsa-slot.jpg')
            ->hideOnIndex()
            ->setColumns(12);

        yield TextField::new('relatedBrandSlug', 'Gerelateerd merk')
            ->setHelp('Optioneel. Bijvoorbeeld: samsonite, american-tourister, delsey.')
            ->hideOnIndex()
            ->setColumns(6);

        yield TextField::new('relatedCategorySlug', 'Gerelateerde categorie')
            ->setHelp('Optioneel. Bijvoorbeeld: koffers, handbagage, rugzakken.')
            ->hideOnIndex()
            ->setColumns(6);

        yield AssociationField::new('relatedProducts', 'Gerelateerde producten')
            ->setHelp('Selecteer producten die onder het artikel getoond mogen worden.')
            ->hideOnIndex()
            ->setColumns(12);

        yield CollectionField::new('faqs', 'Veelgestelde vragen')
            ->useEntryCrudForm(MagazineFaqCrudController::class)
            ->setHelp('FAQ’s worden onder het artikel getoond en later als FAQ schema gebruikt.')
            ->hideOnIndex()
            ->setColumns(12);

        yield DateTimeField::new('publishedAt', 'Publicatiedatum')
            ->setHelp('Wordt automatisch gevuld zodra “Gepubliceerd” aan staat.')
            ->hideOnIndex()
            ->setColumns(6);

        yield DateTimeField::new('createdAt', 'Aangemaakt')
            ->onlyOnDetail();

        yield DateTimeField::new('updatedAt', 'Bijgewerkt')
            ->onlyOnDetail();

        yield DateTimeField::new('publishedAt', 'Gepubliceerd op')
            ->onlyOnIndex();
    }
}