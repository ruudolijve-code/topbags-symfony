<?php

namespace App\Admin\Controller;

use App\Catalog\Entity\Image;
use App\Catalog\Entity\ProductVariant;
use App\Catalog\Service\VariantImageUploader;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Cache\CacheInterface;

class ProductVariantCrudController extends AbstractCrudController
{
    public function __construct(
        private CacheInterface $cache,
        private VariantImageUploader $variantImageUploader
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
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Variant');

        yield AssociationField::new('product', 'Product');

        yield TextField::new('product.brand.name', 'Merk')
        ->onlyOnIndex();

        yield TextField::new('variantSku', 'SKU');

        yield TextField::new('ean', 'EAN')
            ->hideOnIndex();

        yield FormField::addPanel('Prijs & sale');

        yield MoneyField::new('price', 'Normale prijs')
            ->setCurrency('EUR')
            ->setStoredAsCents(false);

        yield MoneyField::new('compareAtPrice', 'Adviesprijs')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->hideOnIndex();

        yield IntegerField::new('salePercentage', 'Sale %')
            ->hideOnIndex();

        yield DateTimeField::new('saleStartsAt', 'Sale start')
            ->hideOnIndex();

        yield DateTimeField::new('saleEndsAt', 'Sale einde')
            ->hideOnIndex();

        yield TextField::new('saleLabel', 'Sale label')
            ->hideOnIndex();

        yield BooleanField::new('isMaster', 'Master');

        yield BooleanField::new('isActive', 'Actief');

        yield FormField::addPanel('Kleur');

        yield AssociationField::new('color', 'Kleur')
            ->hideOnIndex();

        yield AssociationField::new('normalizedColor', 'Genormaliseerde kleur')
            ->hideOnIndex();

        yield TextField::new('supplierColorName', 'Supplier kleur')
            ->hideOnIndex();

        yield SlugField::new('supplierColorSlug', 'Supplier kleur slug')
            ->setTargetFieldName('supplierColorName');    

        yield TextField::new('supplierColorCode', 'Supplier kleurcode')
            ->hideOnIndex();

        yield FormField::addPanel('Voorraad');

        yield IntegerField::new('stockOnHand', 'Op voorraad');

        yield IntegerField::new('stockReserved', 'Gereserveerd')
            ->hideOnIndex();

        yield IntegerField::new('stockAvailable', 'Beschikbaar')
            ->onlyOnIndex();

        yield BooleanField::new('allowBackorder', 'Backorder toegestaan')
            ->renderAsSwitch(false);

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

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ProductVariant) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
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

        $this->handleUploadedImages($entityManager, $entityInstance);
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