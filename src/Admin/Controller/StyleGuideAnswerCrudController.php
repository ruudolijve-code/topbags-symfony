<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\StyleGuide\Entity\StyleGuideAnswer;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\AssociationFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

final class StyleGuideAnswerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return StyleGuideAnswer::class; }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Stijlgidsantwoord')->setEntityLabelInPlural('Stijlgidsantwoorden')
            ->setDefaultSort(['question.position' => 'ASC', 'position' => 'ASC'])->setSearchFields(['question.title', 'code', 'label', 'description']);
    }
    public function configureFilters(Filters $filters): Filters { return $filters->add(AssociationFilter::new('question'))->add(BooleanFilter::new('isActive')); }
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('question', 'Vraag');
        yield TextField::new('code', 'Code');
        yield TextField::new('label', 'Antwoord');
        yield TextareaField::new('description', 'Beschrijving')->setRequired(false)->hideOnIndex();
        yield IntegerField::new('position', 'Volgorde');
        yield BooleanField::new('isActive', 'Actief');
    }
}
