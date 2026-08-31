<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Catalog\Entity\Material;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class MaterialCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Material::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Materiaal')
            ->setEntityLabelInPlural('Materialen')
            ->setPageTitle(Crud::PAGE_INDEX, 'Materialen')
            ->setPageTitle(Crud::PAGE_NEW, 'Materiaal toevoegen')
            ->setPageTitle(Crud::PAGE_EDIT, 'Materiaal bewerken')
            ->setDefaultSort([
                'name' => 'ASC',
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield TextField::new('name', 'Naam');

        yield TextField::new('slug', 'Slug')
            ->setHelp(
                'Bijvoorbeeld: leer, nylon, polyester, rpet.'
            );

        yield IntegerField::new(
            'marketPositionModifier',
            'Marktpositioneringsmodifier'
        )
            ->setHelp(
                'Correctie op de marktpositionering voor de Style Guide. '
                . 'Negatieve waarden verlagen de score, positieve verhogen deze. '
                . 'Advies: -10 t/m +25.'
            );

        yield AssociationField::new('family', 'Materiaalfamilie')
            ->setRequired(false);

        yield NumberField::new('density', 'Dichtheid')
            ->setNumDecimals(3)
            ->setHelp(
                'Optioneel. Kan later worden gebruikt voor gewichtsberekeningen.'
            )
            ->hideOnIndex();

        yield BooleanField::new(
            'isRigid',
            'Hard materiaal'
        );

        yield BooleanField::new(
            'isFlexible',
            'Flexibel materiaal'
        );

        yield IntegerField::new(
            'sustainabilityScore',
            'Duurzaamheidsscore'
        )
            ->setHelp(
                'Optioneel. Bijvoorbeeld een score van 1 t/m 10.'
            )
            ->hideOnIndex();

        yield TextField::new('notes', 'Notities')
            ->hideOnIndex();
    }
}
