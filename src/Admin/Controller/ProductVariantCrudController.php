<?php

namespace App\Admin\Controller;

use App\Catalog\Entity\Image;
use App\Catalog\Entity\ProductVariant;
use App\Catalog\Service\VariantImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

#[IsGranted('ROLE_STORE')]
class ProductVariantCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly VariantImageUploader $variantImageUploader,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ProductVariant::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Variant')
            ->setEntityLabelInPlural('Varianten')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields([
                'variantSku',
                'ean',
                'supplierColorName',
                'supplierColorSlug',
                'supplierColorCode',
                'product.name',
                'product.modelSku',
                'product.brand.name',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);

        if (!$this->isGranted('ROLE_ADMIN')) {
            $actions = $actions
                ->disable(Action::NEW, Action::DELETE);
        }

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        $storeOnly = !$this->isGranted('ROLE_ADMIN');

        yield FormField::addPanel('Variant');

        yield AssociationField::new('product', 'Product')
            ->setFormTypeOption('disabled', $storeOnly);

        yield TextField::new('product.brand.name', 'Merk')
            ->onlyOnIndex();

        yield TextField::new('variantSku', 'SKU')
            ->setFormTypeOption('disabled', $storeOnly);

        yield TextField::new('ean', 'EAN')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', $storeOnly);

        yield FormField::addPanel('Prijs & sale');

        yield MoneyField::new('price', 'Normale prijs')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setFormTypeOption('disabled', $storeOnly);

        yield MoneyField::new('compareAtPrice', 'Adviesprijs')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->hideOnIndex()
            ->setFormTypeOption('disabled', $storeOnly);

        yield IntegerField::new('salePercentage', 'Sale %')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', $storeOnly);

        yield DateTimeField::new('saleStartsAt', 'Sale start')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', $storeOnly);

        yield DateTimeField::new('saleEndsAt', 'Sale einde')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', $storeOnly);

        yield TextField::new('saleLabel', 'Sale label')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', $storeOnly);

        yield BooleanField::new('isMaster', 'Master')
            ->setFormTypeOption('disabled', $storeOnly);

        yield BooleanField::new('isActive', 'Actief')
            ->setFormTypeOption('disabled', $storeOnly);

        yield FormField::addPanel('Kleur');

        yield AssociationField::new('color', 'Kleur')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', $storeOnly);

        yield AssociationField::new('normalizedColor', 'Genormaliseerde kleur')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', $storeOnly);

        yield TextField::new('supplierColorName', 'Supplier kleur')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', $storeOnly);

        yield SlugField::new('supplierColorSlug', 'Supplier kleur slug')
            ->setTargetFieldName('supplierColorName')
            ->setFormTypeOption('disabled', $storeOnly);

        yield TextField::new('supplierColorCode', 'Supplier kleurcode')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', $storeOnly);

        yield FormField::addPanel('Voorraad');

        yield IntegerField::new('stockOnHand', 'Op voorraad');

        yield IntegerField::new('stockReserved', 'Gereserveerd')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', $storeOnly)
            ->setHelp('Gereserveerde voorraad wordt normaal door bestellingen bepaald.');

        yield IntegerField::new('stockAvailable', 'Beschikbaar')
            ->onlyOnIndex();

        yield BooleanField::new('allowBackorder', 'Backorder toegestaan')
            ->renderAsSwitch(false);

        yield FormField::addPanel('SEO')
            ->setHelp(
                'Laat deze velden leeg om automatisch een SEO-titel en meta-omschrijving te gebruiken.'
            );

        yield TextField::new('seoTitle', 'SEO-titel')
            ->hideOnIndex()
            ->setMaxLength(255)
            ->setHelp(
                'Optionele override. Automatisch voorbeeld: Merk + productnaam + kleur | Topbags'
            )
            ->setFormTypeOption('disabled', $storeOnly);

        yield TextareaField::new('seoDescription', 'Meta-omschrijving')
            ->hideOnIndex()
            ->setNumOfRows(4)
            ->setHelp(
                'Optionele override. Laat leeg om automatisch een omschrijving samen te stellen uit product- en variantgegevens.'
            )
            ->setFormTypeOption('disabled', $storeOnly);

        yield TextareaField::new('heroIntro', 'Hero-intro')
            ->hideOnIndex()
            ->setNumOfRows(3)
            ->setHelp(
                'Zichtbare korte tekst onder de producttitel. Laat leeg voor automatische tekst.'
            )
            ->setFormTypeOption('disabled', $storeOnly);

        if ($this->isGranted('ROLE_ADMIN')) {
            yield FormField::addPanel('Afbeeldingen');

            yield Field::new('uploadedImages', 'Afbeeldingen uploaden')
                ->setFormType(FileType::class)
                ->setFormTypeOptions([
                    'multiple' => true,
                    'required' => false,
                    'mapped' => true,
                    'attr' => [
                        'accept' => 'image/*',
                    ],
                ])
                ->onlyOnForms();

            yield CollectionField::new('images', 'Bestaande afbeeldingen')
                ->useEntryCrudForm(ImageCrudController::class)
                ->setFormTypeOption('allow_add', false)
                ->setFormTypeOption('allow_delete', true)
                ->setHelp('Nieuwe afbeeldingen voeg je hierboven toe via upload.')
                ->hideOnIndex();
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ProductVariant) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Winkelmedewerkers mogen geen nieuwe varianten aanmaken.');
        }

        parent::persistEntity($entityManager, $entityInstance);

        $this->handleUploadedImages($entityManager, $entityInstance);
        $this->invalidateAvailabilityCache($entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ProductVariant) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        parent::updateEntity($entityManager, $entityInstance);

        if ($this->isGranted('ROLE_ADMIN')) {
            $this->handleUploadedImages($entityManager, $entityInstance);
        }

        $this->invalidateAvailabilityCache($entityInstance);
    }

    private function handleUploadedImages(
        EntityManagerInterface $entityManager,
        ProductVariant $variant
    ): void {
        $files = $variant->getUploadedImages();

        if ($files === null || $files === []) {
            return;
        }

        $hasPrimary = false;
        $nextPosition = 0;

        foreach ($variant->getImages() as $existingImage) {
            if ($existingImage->isPrimary()) {
                $hasPrimary = true;
            }

            $nextPosition = max($nextPosition, $existingImage->getPosition() + 1);
        }

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $filename = $this->variantImageUploader->upload($file, $variant);

            $image = new Image();
            $image
                ->setProductVariant($variant)
                ->setFilename($filename)
                ->setPosition($nextPosition++)
                ->setIsPrimary(!$hasPrimary && $index === 0);

            $entityManager->persist($image);
            $variant->addImage($image);
        }

        $variant->setUploadedImages(null);

        $entityManager->flush();
    }

    private function invalidateAvailabilityCache(ProductVariant $variant): void
    {
        if ($variant->getId() === null) {
            return;
        }

        $this->cache->delete('availability.variant.' . $variant->getId());
    }
}