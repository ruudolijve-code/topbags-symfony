<?php

namespace App\Admin\Controller;

use App\Catalog\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class ImageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Image::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Afbeelding')
            ->setEntityLabelInPlural('Afbeeldingen');
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImageField::new('previewUrl', 'Preview')
            ->setBasePath('/')
            ->hideOnForm();

        yield IntegerField::new('position', 'Positie');

        yield BooleanField::new('isPrimary', 'Primaire afbeelding');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Image) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        $this->syncPrimaryImageState($entityManager, $entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Image) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $this->syncPrimaryImageState($entityManager, $entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function syncPrimaryImageState(EntityManagerInterface $entityManager, Image $currentImage): void
    {
        $variant = $currentImage->getProductVariant();

        if (!$currentImage->isPrimary()) {
            $hasOtherPrimary = false;

            foreach ($variant->getImages() as $image) {
                if ($image === $currentImage) {
                    continue;
                }

                if ($image->isPrimary()) {
                    $hasOtherPrimary = true;
                    break;
                }
            }

            if (!$hasOtherPrimary) {
                $currentImage->setIsPrimary(true);
            }

            return;
        }

        foreach ($variant->getImages() as $image) {
            if ($image === $currentImage) {
                continue;
            }

            if ($image->isPrimary()) {
                $image->setIsPrimary(false);
                $entityManager->persist($image);
            }
        }
    }
}