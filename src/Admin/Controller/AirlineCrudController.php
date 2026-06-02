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
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
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
            ->setPageTitle(
                Crud::PAGE_EDIT,
                static fn (Airline $airline): string => sprintf('Aanpassen: %s', $airline->getName())
            )
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields([
                'name',
                'slug',
                'iataCode',
                'hint',
                'seoTitle',
                'seoDescription',
                'seoH1',
                'seoIntro',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(
                Crud::PAGE_INDEX,
                Action::NEW,
                static fn (Action $action): Action => $action->setLabel('Vliegmaatschappij toevoegen')
            )
            ->update(
                Crud::PAGE_INDEX,
                Action::EDIT,
                static fn (Action $action): Action => $action->setLabel('Aanpassen')
            )
            ->update(
                Crud::PAGE_INDEX,
                Action::DELETE,
                static fn (Action $action): Action => $action->setLabel('Verwijderen')
            );
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Algemeen');

        yield IdField::new('id')
            ->hideOnForm();

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

        yield FormField::addPanel('SEO');

        yield TextField::new('seoTitle', 'SEO titel')
            ->setRequired(false)
            ->setHelp('Bijvoorbeeld: Ryanair bagageregels 2026 | Handbagage, koffer & personal item');

        yield TextareaField::new('seoDescription', 'Meta description')
            ->setRequired(false)
            ->setNumOfRows(3)
            ->setHelp('Korte omschrijving voor Google. Richtlijn: ongeveer 140-160 tekens.');

        yield TextField::new('seoH1', 'H1 titel')
            ->setRequired(false)
            ->setHelp('Bijvoorbeeld: Ryanair bagageregels: welke koffer mag mee?');

        yield TextareaField::new('seoIntro', 'Intro tekst')
            ->setRequired(false)
            ->setNumOfRows(5)
            ->setHelp('Korte introductie bovenaan de airline-pagina.');

        yield TextField::new('canonicalUrl', 'Canonical URL')
            ->setRequired(false)
            ->setHelp('Alleen invullen als je wilt afwijken van de standaard URL.');

        yield BooleanField::new('isIndexable', 'Indexeerbaar')
            ->setHelp('Uit = noindex. Aan = index, follow.');
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
        $slug = trim($airline->getSlug());

        if ($slug === '') {
            $slug = $airline->getName();
        }

        $airline->setSlug(
            strtolower($this->slugger->slug($slug)->toString())
        );
    }
}