<?php

declare(strict_types=1);

namespace App\StyleGuide\Service;

use App\Catalog\Entity\Product;
use App\Catalog\Repository\ProductRepository;
use App\StyleGuide\ValueObject\StyleGuideCriteria;
use App\StyleGuide\Service\Recommendation\StyleGuideCategorySlugMapper;

final class StyleGuideCatalogCandidateFinder
{
    private const DEFAULT_LIMIT = 300;

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly MaterialPreferenceMapper $materialMapper,
        private readonly StyleGuideCategorySlugMapper $categorySlugMapper,
    ) {
    }

    /**
     * Brede commerciële voorselectie.
     *
     * De fysieke geschiktheid wordt daarna gecontroleerd
     * door StyleGuideFitCandidateFilter.
     *
     * @return list<Product>
     */
    public function find(
        StyleGuideCriteria $criteria,
        int $limit = self::DEFAULT_LIMIT,
    ): array {
        $materialSlugs = $this->materialMapper->getMaterialSlugs(
            $criteria->materialPreference,
        );

        return $this->productRepository
            ->findStyleGuideCatalogCandidates(
                materialSlugs: $materialSlugs,
                strictMaterialFilter: $this->materialMapper->isStrict(
                    $criteria->materialPreference,
                ),
                audienceCategorySlugs: $this->categorySlugMapper->audience($criteria),
                carryMethodCategorySlugs: $this->categorySlugMapper->carryMethod($criteria),
                limit: $limit,
            );
    }
}
