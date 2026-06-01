<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Guide\Entity\AirlineTicketType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_ADMIN')]
final class AirlineTicketTypeCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return AirlineTicketType::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Tickettype')
            ->setEntityLabelInPlural('Tickettypes')
            ->setPageTitle(Crud::PAGE_INDEX, 'Tickettypes')
            ->setPageTitle(Crud::PAGE_NEW, 'Nieuw tickettype')
            ->setPageTitle(Crud::PAGE_EDIT, fn (AirlineTicketType $ticket) => sprintf('Aanpassen: %s', $ticket->getName()))
            ->setDefaultSort(['airline.name' => 'ASC', 'priorityLevel' => 'ASC'])
            ->setSearchFields(['name', 'slug', 'description', 'airline.name']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setLabel('Tickettype toevoegen'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setLabel('Aanpassen'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->setLabel('Verwijderen'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield BooleanField::new('isActive', 'Actief');

        yield AssociationField::new('airline', 'Vliegmaatschappij')
            ->setRequired(true);

        yield IntegerField::new('priorityLevel', 'Volgorde')
            ->setHelp('Lager nummer komt eerder op de airline-pagina.');

        yield TextField::new('name', 'Naam')
            ->setHelp('Bijvoorbeeld: Basic, Smart, Priority, Light, Flex.');

        yield TextField::new('slug', 'Slug')
            ->setRequired(false)
            ->setHelp('Mag leeg blijven. Wordt automatisch gemaakt op basis van de naam.');

        yield TextareaField::new('description', 'Omschrijving')
            ->setRequired(false)
            ->hideOnIndex();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof AirlineTicketType) {
            return;
        }

        $this->normalize($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof AirlineTicketType) {
            return;
        }

        $this->normalize($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function normalize(AirlineTicketType $ticket): void
    {
        if (trim($ticket->getSlug()) === '') {
            $ticket->setSlug(
                strtolower($this->slugger->slug($ticket->getName())->toString())
            );
        } else {
            $ticket->setSlug(
                strtolower($this->slugger->slug($ticket->getSlug())->toString())
            );
        }
    }
}