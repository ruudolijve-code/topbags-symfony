<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Magazine\Entity\MagazineArticle;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

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
            ->setDefaultSort([
                'publishedAt' => 'DESC',
                'id' => 'DESC',
            ])
            ->setSearchFields([
                'title',
                'slug',
                'context',
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
            ->add('context')
            ->add('isPublished')
            ->add('isFeatured')
            ->add('category')
            ->add('publishedAt')
            ->add('relatedBrandSlug')
            ->add('relatedCategorySlug');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        /*
         * Magazinecontext
         *
         * shop = koffers, reizen, bagage en luchtvaart
         * bags = tassen, rugzakken, accessoires en lifestyle
         */
        yield ChoiceField::new('context', 'Magazine')
            ->setChoices([
                'Koffers & reizen' => MagazineArticle::CONTEXT_SHOP,
                'Tassen & accessoires' => MagazineArticle::CONTEXT_BAGS,
            ])
            ->renderAsBadges([
                MagazineArticle::CONTEXT_SHOP => 'info',
                MagazineArticle::CONTEXT_BAGS => 'warning',
            ])
            ->setHelp(
                'Bepaalt op welke magazine-overzichtspagina het artikel wordt getoond.'
            )
            ->setColumns(4);

        yield BooleanField::new('isPublished', 'Gepubliceerd');

        yield BooleanField::new('isFeatured', 'Uitgelicht')
            ->setHelp(
                'Toon dit artikel als groot uitgelicht artikel binnen de gekozen magazinecontext.'
            );

        yield TextField::new('title', 'Titel')
            ->setColumns(8);

        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('title')
            ->setUnlockConfirmationMessage(
                'De slug wordt normaal automatisch gemaakt op basis van de titel. Weet je zeker dat je deze handmatig wilt aanpassen?'
            )
            ->hideOnIndex()
            ->setColumns(8);

        yield ChoiceField::new('category', 'Categorie')
            ->setChoices([
                /*
                 * Koffers & reizen
                 */
                'Koffers' => 'Koffers',
                'Handbagage' => 'Handbagage',
                'Reistassen' => 'Reistassen',
                'Vliegreizen' => 'Vliegreizen',
                'Reistips' => 'Reistips',
                'Reparatie & service' => 'Reparatie & service',

                /*
                 * Tassen & accessoires
                 */
                'Damestassen' => 'Damestassen',
                'Laptoptassen' => 'Laptoptassen',
                'Rugzakken' => 'Rugzakken',
                'Portemonnees' => 'Portemonnees',
                'Accessoires' => 'Accessoires',
                'Leer & onderhoud' => 'Leer & onderhoud',
                'Mode & inspiratie' => 'Mode & inspiratie',

                /*
                 * Algemeen
                 */
                'Nieuws' => 'Nieuws',
            ])
            ->renderExpanded(false)
            ->setHelp(
                'Kies een categorie die aansluit bij de geselecteerde magazinecontext.'
            )
            ->setColumns(4);

        yield TextareaField::new('excerpt', 'Korte intro')
            ->setHelp(
                'Korte samenvatting voor de overzichtspagina en als intro boven het artikel.'
            )
            ->hideOnIndex()
            ->setColumns(12);

        yield TextEditorField::new('content', 'Artikeltekst')
            ->setHelp(
                'Gebruik duidelijke tussenkoppen, alinea’s, lijsten en interne links.'
            )
            ->hideOnIndex()
            ->setColumns(12);

        yield TextField::new('seoTitle', 'SEO-titel')
            ->setHelp(
                'Laat leeg om automatisch de gewone artikeltitel te gebruiken.'
            )
            ->hideOnIndex()
            ->setColumns(6);

        yield TextareaField::new('seoDescription', 'SEO-omschrijving')
            ->setHelp(
                'Gebruik bij voorkeur ongeveer 140 tot 160 tekens.'
            )
            ->hideOnIndex()
            ->setColumns(6);

        yield TextField::new('heroImage', 'Hero-afbeelding')
            ->setHelp(
                'Optioneel pad, bijvoorbeeld /images/magazine/tsa-slot.jpg.'
            )
            ->hideOnIndex()
            ->setColumns(12);

       AssociationField::new('relatedBrands', 'Gerelateerde merken')
            ->autocomplete()
            ->setHelp(
                'Selecteer de merken die inhoudelijk bij dit artikel horen. Deze worden als klikbare merklinks onder het artikel getoond.'
            )
            ->hideOnIndex()
            ->setColumns(12);

        yield TextField::new(
            'relatedCategorySlug',
            'Gerelateerde productcategorie'
        )
            ->setHelp(
                'Optioneel. Bijvoorbeeld koffers, handbagage, damestassen of rugzakken.'
            )
            ->hideOnIndex()
            ->setColumns(6);

        yield AssociationField::new(
            'relatedProducts',
            'Gerelateerde producten'
        )
            ->setHelp(
                'Selecteer producten die onder het artikel mogen worden getoond. Kies producten uit dezelfde context als het artikel.'
            )
            ->hideOnIndex()
            ->setColumns(12);

        yield CollectionField::new('faqs', 'Veelgestelde vragen')
            ->useEntryCrudForm(MagazineFaqCrudController::class)
            ->setHelp(
                'Actieve FAQ’s worden onder het artikel getoond en opgenomen in FAQ structured data.'
            )
            ->hideOnIndex()
            ->setColumns(12);

        if ($pageName === Crud::PAGE_INDEX) {
            yield DateTimeField::new('publishedAt', 'Gepubliceerd op')
                ->setFormat('dd-MM-yyyy HH:mm');
        } else {
            yield DateTimeField::new('publishedAt', 'Publicatiedatum')
                ->setHelp(
                    'Wordt automatisch gevuld zodra “Gepubliceerd” wordt ingeschakeld, maar kan handmatig worden aangepast.'
                )
                ->setColumns(6);
        }

        yield DateTimeField::new('createdAt', 'Aangemaakt')
            ->onlyOnDetail();

        yield DateTimeField::new('updatedAt', 'Bijgewerkt')
            ->onlyOnDetail();
    }
}