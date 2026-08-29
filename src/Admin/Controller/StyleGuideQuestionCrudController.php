<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\StyleGuide\Entity\StyleGuideQuestion;
use App\StyleGuide\Enum\SelectionType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class StyleGuideQuestionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return StyleGuideQuestion::class; }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Stijlgidsvraag')->setEntityLabelInPlural('Stijlgidsvragen')
            ->setDefaultSort(['position' => 'ASC'])->setSearchFields(['code', 'title', 'subtitle', 'description']);
    }
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('code', 'Code')->setHelp('Technische sleutel; wijzig bestaande codes alleen samen met code-aanpassingen.');
        yield TextField::new('title', 'Vraag');
        yield TextField::new('subtitle', 'Subtitel')->setRequired(false);
        yield TextareaField::new('description', 'Beschrijving')->setRequired(false);
        yield TextareaField::new('helpText', 'Helptekst')->setRequired(false)->hideOnIndex();
        yield ChoiceField::new('selectionType', 'Selectietype')->setChoices(['Eén antwoord' => SelectionType::SINGLE, 'Meerdere antwoorden' => SelectionType::MULTIPLE]);
        yield IntegerField::new('position', 'Volgorde');
        yield BooleanField::new('isActive', 'Actief');
    }
}
