<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Guide\Entity\AirlineBaggageRule;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AirlineBaggageRuleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AirlineBaggageRule::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Bagageregel')
            ->setEntityLabelInPlural('Bagageregels')
            ->setPageTitle(Crud::PAGE_INDEX, 'Bagageregels')
            ->setPageTitle(Crud::PAGE_NEW, 'Nieuwe bagageregel')
            ->setPageTitle(Crud::PAGE_EDIT, fn (AirlineBaggageRule $rule) => sprintf('Bagageregel aanpassen: %s', (string) $rule))
            ->setDefaultSort(['airline.name' => 'ASC', 'ticketType.priorityLevel' => 'ASC', 'ruleScope' => 'ASC'])
            ->setSearchFields(['airline.name', 'ticketType.name', 'ruleScope', 'dimensionType']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setLabel('Bagageregel toevoegen'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setLabel('Aanpassen'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->setLabel('Verwijderen'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield BooleanField::new('isActive', 'Actief');

        yield AssociationField::new('airline', 'Vliegmaatschappij')
            ->setRequired(true);

        yield AssociationField::new('ticketType', 'Tickettype')
            ->setRequired(true)
            ->setHelp('Kies het tickettype dat bij deze airline hoort.');

        yield ChoiceField::new('ruleScope', 'Bagagetype')
            ->setChoices([
                'Personal item / onder stoel' => AirlineBaggageRule::SCOPE_PERSONAL,
                'Handbagage' => AirlineBaggageRule::SCOPE_CABIN,
                'Ruimbagage' => AirlineBaggageRule::SCOPE_HOLD,
            ])
            ->setRequired(true);

        yield ChoiceField::new('dimensionType', 'Type afmeting')
            ->setChoices([
                'Hoogte × breedte × diepte' => AirlineBaggageRule::DIMENSION_BOX,
                'Lineaire som' => AirlineBaggageRule::DIMENSION_LINEAR_SUM,
            ])
            ->setRequired(true);

        yield IntegerField::new('quantityCabin', 'Aantal stuks')
            ->setRequired(false)
            ->setHelp('Gebruik dit voor aantal toegestane stuks. Geldt ook voor personal item of ruimbagage.');

        yield IntegerField::new('maxHeightCm', 'Hoogte cm')
            ->setRequired(false);

        yield IntegerField::new('maxWidthCm', 'Breedte cm')
            ->setRequired(false);

        yield IntegerField::new('maxDepthCm', 'Diepte cm')
            ->setRequired(false);

        yield IntegerField::new('maxLinearCm', 'Max. lineaire cm')
            ->setRequired(false)
            ->setHelp('Alleen gebruiken bij lineaire som, bijvoorbeeld 158 cm.');

        yield NumberField::new('maxWeightKg', 'Max. gewicht kg')
            ->setRequired(false)
            ->setNumDecimals(1);
    }
}