<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Guide\Entity\Airline;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_ADMIN')]
final class AirlineCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Airline::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Vliegmaatschappij')
            ->setEntityLabelInPlural('Vliegmaatschappijen')
            ->setPageTitle(Crud::PAGE_INDEX, 'Vliegmaatschappijen')
            ->setPageTitle(Crud::PAGE_NEW, 'Nieuwe vliegmaatschappij')
            ->setPageTitle(Crud::PAGE_EDIT, fn (Airline $airline) => sprintf('Aanpassen: %s', $airline->getName()))
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name', 'slug', 'iataCode', 'hint']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setLabel('Vliegmaatschappij toevoegen'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setLabel('Aanpassen'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->setLabel('Verwijderen'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield BooleanField::new('isActive', 'Actief');

        yield TextField::new('name', 'Naam')
            ->setHelp('Bijvoorbeeld: TUIfly, Transavia, KLM.');

        yield TextField::new('slug', 'Slug')
            ->setRequired(false)
            ->setHelp('Mag leeg blijven. Wordt automatisch gemaakt op basis van de naam.');

        yield TextField::new('iataCode', 'IATA-code')
            ->setHelp('Bijvoorbeeld: HV, KL, OR. Gebruik eventueel een herkenbare code als onbekend.');

        yield TextField::new('logo', 'Logo-bestand')
            ->setRequired(false)
            ->setHelp('Bestandsnaam in /public/images/airline/, bijvoorbeeld tuifly.svg.');

        yield TextField::new('hint', 'Korte hint')
            ->setRequired(false)
            ->setHelp('Korte toelichting voor de bagagegids of airline-pagina.');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Airline) {
            return;
        }

        $this->normalize($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Airline) {
            return;
        }

        $this->normalize($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function normalize(Airline $airline): void
    {
        if (trim($airline->getSlug()) === '') {
            $airline->setSlug(
                strtolower($this->slugger->slug($airline->getName())->toString())
            );
        } else {
            $airline->setSlug(
                strtolower($this->slugger->slug($airline->getSlug())->toString())
            );
        }
    }
}