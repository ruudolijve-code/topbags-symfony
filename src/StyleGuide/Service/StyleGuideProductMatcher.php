<?php

declare(strict_types=1);

namespace App\StyleGuide\Service;

use App\StyleGuide\Service\Recommendation\StyleGuideFitCandidateFilter;
use App\StyleGuide\Service\Recommendation\StyleGuideCategoryCandidateFilter;
use App\StyleGuide\ValueObject\BagFitProfile;
use App\StyleGuide\ValueObject\BagRecommendationProfile;
use App\StyleGuide\ValueObject\ProductMatch;
use App\StyleGuide\ValueObject\StyleGuideCriteria;

final class StyleGuideProductMatcher
{
    public function __construct(
        private readonly StyleGuideCatalogCandidateFinder $catalogCandidateFinder,
        private readonly StyleGuideFitCandidateFilter $fitCandidateFilter,
        private readonly StyleGuideCategoryCandidateFilter $categoryCandidateFilter,
        private readonly StyleGuideProductScorer $productScorer,
        private readonly StyleGuideProductRanker $productRanker,
    ) {
    }

    /**
     * @return list<ProductMatch>
     */
    public function match(
        StyleGuideCriteria $criteria,
        BagFitProfile $fitProfile,
        BagRecommendationProfile $recommendationProfile,
        int $limit = 24,
    ): array {
        /*
         * Stap 1:
         * brede commerciële voorselectie.
         *
         * Context, materiaal, budget en later categorieën.
         */
        $candidates = $this->catalogCandidateFinder->find(
            criteria: $criteria,
            limit: 300,
        );

        /*
         * Stap 2: harde doelgroep- en draagwijzefilters op bestaande categorieën.
         */
        $candidates = $this->categoryCandidateFilter->filter($candidates, $criteria);

        /*
         * Stap 3:
         * harde fysieke geschiktheid.
         *
         * Afmetingen, laptopvak en laptopmaat.
         */
        $candidates = $this->fitCandidateFilter->filter(
            products: $candidates,
            fitProfile: $fitProfile,
        );

        /*
         * Stap 4:
         * kwaliteit van iedere match bepalen.
         */
        $matches = [];

        foreach ($candidates as $product) {
            $matches[] = $this->productScorer->score(
                product: $product,
                criteria: $criteria,
                fitProfile: $fitProfile,
                recommendationProfile: $recommendationProfile,
            );
        }

        /*
         * Stap 5:
         * beste matches sorteren en begrenzen.
         */
        return $this->productRanker->rank(
            matches: $matches,
            limit: $limit,
        );
    }
}
