<?php

namespace App\Shared\Twig;

use App\Catalog\Repository\CategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class MenuExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    public function getGlobals(): array
    {
        return [
            'menuShopCategories' => $this->categoryRepository->findForContext('shop'),
            'menuBagsCategories' => $this->categoryRepository->findForContext('bags'),
        ];
    }
}