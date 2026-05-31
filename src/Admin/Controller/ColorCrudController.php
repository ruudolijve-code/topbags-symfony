<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Catalog\Entity\Color;
use App\Catalog\Entity\ProductVariant;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ColorCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Color::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Kleur')
            ->setEntityLabelInPlural('Kleuren')
            ->setPageTitle(Crud::PAGE_INDEX, 'Kleuren')
            ->setPageTitle(Crud::PAGE_NEW, 'Nieuwe kleur')
            ->setPageTitle(Crud::PAGE_EDIT, fn (Color $color) => sprintf('Kleur aanpassen: %s', $color->getName()))
            ->setDefaultSort(['family' => 'ASC', 'name' => 'ASC'])
            ->setSearchFields(['name', 'slug', 'hex', 'family']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setLabel('Kleur toevoegen'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setLabel('Aanpassen'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->setLabel('Verwijderen'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield TextField::new('name', 'Naam')
            ->setHelp('Bijvoorbeeld: Donkerblauw, Taupe, Burgundy.');

        yield TextField::new('slug', 'Slug')
            ->setRequired(false)
            ->setHelp('Mag leeg blijven. Wordt automatisch gevuld op basis van de naam.');

        if (Crud::PAGE_INDEX === $pageName || Crud::PAGE_DETAIL === $pageName) {
            yield ColorField::new('hex', 'Kleur')
                ->setRequired(false);

            yield TextField::new('hex', 'Hex-code')
                ->onlyOnIndex();
        } else {
            yield TextField::new('hex', 'Hex-code')
                ->setRequired(false)
                ->setHelp('Bijvoorbeeld: #1F4080 of 1F4080. De # wordt automatisch toegevoegd als je die vergeet.');
        }

        yield ChoiceField::new('swatchType', 'Swatch type')
            ->setChoices([
                'Effen kleur' => 'solid',
                'Patroon / afbeelding' => 'pattern',
            ])
            ->setRequired(true);

        yield TextField::new('swatchValue', 'Swatch waarde')
            ->setRequired(false)
            ->setHelp('Alleen nodig bij patroon/afbeelding. Bij effen kleur leeg laten.');

        yield ChoiceField::new('family', 'Kleurfamilie')
            ->setChoices([
                'Beige' => 'beige',
                'Blauw' => 'blauw',
                'Bruin' => 'bruin',
                'Geel' => 'geel',
                'Grijs' => 'grijs',
                'Groen' => 'groen',
                'Oranje' => 'oranje',
                'Paars' => 'paars',
                'Rood' => 'rood',
                'Roze' => 'roze',
                'Wit' => 'wit',
                'Zwart' => 'zwart',
            ])
            ->setRequired(true);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Color) {
            return;
        }

        $this->normalizeColor($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Color) {
            return;
        }

        $this->normalizeColor($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Color) {
            return;
        }

        $usageCount = $this->countColorUsage($entityManager, $entityInstance);

        if ($usageCount > 0) {
            $this->addFlash(
                'danger',
                sprintf(
                    'Deze kleur kan niet worden verwijderd, omdat deze nog wordt gebruikt door %d variant(en).',
                    $usageCount
                )
            );

            return;
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    private function normalizeColor(Color $color): void
    {
        $name = trim($color->getName());
        $color->setName($name);

        $slug = trim($color->getSlug());

        if ($slug === '') {
            $slug = $name;
        }

        $color->setSlug(
            strtolower($this->slugger->slug($slug)->toString())
        );

        $hex = $color->getHex();

        if ($hex !== null && trim($hex) !== '') {
            $hex = strtoupper(trim($hex));

            if (!str_starts_with($hex, '#')) {
                $hex = '#' . $hex;
            }

            if (!preg_match('/^#[0-9A-F]{6}$/', $hex)) {
                throw new \InvalidArgumentException('Ongeldige hex-code. Gebruik bijvoorbeeld #1F4080.');
            }

            $color->setHex($hex);
        } else {
            $color->setHex(null);
        }

        if (trim($color->getSwatchType()) === '') {
            $color->setSwatchType('solid');
        }

        if ($color->getSwatchType() === 'solid') {
            $color->setSwatchValue(null);
        }
    }

    private function countColorUsage(EntityManagerInterface $entityManager, Color $color): int
    {
        return (int) $entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT v.id)')
            ->from(ProductVariant::class, 'v')
            ->where('v.color = :color')
            ->orWhere('v.normalizedColor = :color')
            ->setParameter('color', $color)
            ->getQuery()
            ->getSingleScalarResult();
    }
}