<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Guide\Entity\Faq;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class FaqCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Faq::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('FAQ')
            ->setEntityLabelInPlural('FAQ’s')
            ->setDefaultSort([
                'transportType' => 'ASC',
                'airline.name' => 'ASC',
                'position' => 'ASC',
                'id' => 'ASC',
            ])
            ->setSearchFields([
                'question',
                'answer',
                'transportType',
                'airline.name',
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield ChoiceField::new('transportType', 'Type')
            ->setChoices([
                'Vliegmaatschappij' => 'plane',
            ]);

        yield AssociationField::new('airline', 'Vliegmaatschappij')
            ->setRequired(false)
            ->setHelp('Koppel deze FAQ aan een vliegmaatschappij, bijvoorbeeld Ryanair.');

        yield TextField::new('question', 'Vraag');

        yield TextareaField::new('answer', 'Antwoord')
            ->setNumOfRows(6);

        yield IntegerField::new('position', 'Volgorde');

        yield BooleanField::new('isActive', 'Actief');

        yield DateTimeField::new('createdAt', 'Aangemaakt')
            ->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Gewijzigd')
            ->hideOnForm();
    }
}