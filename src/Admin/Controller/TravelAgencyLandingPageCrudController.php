<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Guide\Entity\TravelAgencyLandingPage;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\String\Slugger\SluggerInterface;

final class TravelAgencyLandingPageCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return TravelAgencyLandingPage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Reisbureau landingspagina')
            ->setEntityLabelInPlural('Reisbureau landingspagina’s')
            ->setPageTitle(Crud::PAGE_INDEX, 'Reisbureau landingspagina’s')
            ->setPageTitle(Crud::PAGE_NEW, 'Nieuwe reisbureaupagina')
            ->setPageTitle(Crud::PAGE_EDIT, fn (TravelAgencyLandingPage $page) => sprintf('Aanpassen: %s', $page->getName()))
            ->setDefaultSort(['position' => 'ASC', 'city' => 'ASC', 'name' => 'ASC'])
            ->setSearchFields(['name', 'slug', 'city', 'agencyType', 'seoTitle']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setLabel('Pagina toevoegen'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setLabel('Aanpassen'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->setLabel('Verwijderen'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield BooleanField::new('isActive', 'Actief');

        yield IntegerField::new('position', 'Volgorde')
            ->setHelp('Lager nummer komt eerder.');

        yield TextField::new('name', 'Naam')
            ->setHelp('Bijvoorbeeld: TUI Hengelo of D-reizen Enschede.');

        yield TextField::new('slug', 'Slug')
            ->setRequired(false)
            ->setHelp('Mag leeg blijven. Wordt automatisch gemaakt, bijvoorbeeld tui-hengelo.');

        yield TextField::new('city', 'Plaats')
            ->setHelp('Bijvoorbeeld: Hengelo, Enschede of Almelo.');

        yield ChoiceField::new('agencyType', 'Type reisbureau')
            ->setChoices([
                'TUI' => 'tui',
                'D-reizen' => 'd-reizen',
                'Onafhankelijk' => 'onafhankelijk',
                'Anders' => 'anders',
            ])
            ->setRequired(false);

        yield TextField::new('seoTitle', 'SEO title')
            ->setRequired(false)
            ->setHelp('Bijvoorbeeld: Reis geboekt bij TUI Hengelo? Check je bagage | Topbags');

        yield TextareaField::new('seoDescription', 'SEO description')
            ->setRequired(false)
            ->setHelp('Maximaal ongeveer 155 tekens.');

        yield TextField::new('h1', 'H1')
            ->setRequired(false)
            ->setHelp('Bijvoorbeeld: Reis geboekt bij TUI Hengelo? Check je koffer en handbagage');

        yield TextareaField::new('introText', 'Intro tekst')
            ->setRequired(false)
            ->hideOnIndex();

        yield TextareaField::new('bodyText', 'SEO tekst')
            ->setRequired(false)
            ->hideOnIndex();

        yield TextareaField::new('partnerText', 'Partnertekst')
            ->setRequired(false)
            ->hideOnIndex()
            ->setHelp('Tekst voor reisbureaus die naar de bagagegids willen verwijzen.');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof TravelAgencyLandingPage) {
            return;
        }

        $this->normalize($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof TravelAgencyLandingPage) {
            return;
        }

        $this->normalize($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function normalize(TravelAgencyLandingPage $page): void
    {
        if (trim($page->getSlug()) === '') {
            $slugBase = sprintf('%s %s', $page->getName(), $page->getCity());
        } else {
            $slugBase = $page->getSlug();
        }

        $page->setSlug(
            strtolower($this->slugger->slug($slugBase)->toString())
        );
    }
}