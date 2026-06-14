<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Magazine\Entity\MagazineFaq;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class MagazineFaqCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MagazineFaq::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('FAQ')
            ->setEntityLabelInPlural('FAQ’s')
            ->setPageTitle(Crud::PAGE_NEW, 'Nieuwe FAQ')
            ->setPageTitle(Crud::PAGE_EDIT, 'FAQ bewerken');
    }

    public function configureFields(string $pageName): iterable
    {
        yield BooleanField::new('isActive', 'Actief');

        yield IntegerField::new('position', 'Volgorde')
            ->setHelp('Laagste nummer wordt eerst getoond.');

        yield TextField::new('question', 'Vraag')
            ->setColumns(12);

        yield TextareaField::new('answer', 'Antwoord')
            ->setColumns(12);
    }
}