<?php

namespace App\Controller\Admin;

use App\Seo\Entity\Redirect;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

final class RedirectCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Redirect::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Redirect')
            ->setEntityLabelInPlural('Redirects')
            ->setDefaultSort(['oldPath' => 'ASC'])
            ->setSearchFields(['oldPath', 'newUrl']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isActive', 'Actief'));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),

            TextField::new('oldPath', 'Oud pad')
                ->setHelp('Bijvoorbeeld: /onze-winkel of /tassen/crossbody.html'),

            TextField::new('newUrl', 'Nieuwe URL')
                ->setHelp('Bijvoorbeeld: /winkel of /categorie/crossbody-tassen'),

            BooleanField::new('isActive', 'Actief'),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Redirect) {
            $entityInstance->setOldPath($this->normalizePath($entityInstance->getOldPath()));
            $entityInstance->setNewUrl($this->normalizeNewUrl($entityInstance->getNewUrl()));
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Redirect) {
            $entityInstance->setOldPath($this->normalizePath($entityInstance->getOldPath()));
            $entityInstance->setNewUrl($this->normalizeNewUrl($entityInstance->getNewUrl()));
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '/';
        }

        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        $path = parse_url($path, PHP_URL_PATH) ?: '/';

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    private function normalizeNewUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '/';
        }

        if (
            str_starts_with($url, 'http://') ||
            str_starts_with($url, 'https://')
        ) {
            return $url;
        }

        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }

        return $url;
    }
}