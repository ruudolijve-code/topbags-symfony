<?php

declare(strict_types=1);

namespace App\StyleGuide\ValueObject;

use App\StyleGuide\Enum\CarryMethod;
use App\StyleGuide\Enum\RecommendedBagCategory;

final readonly class BagRecommendationProfile
{
    /**
     * @param list<string> $features
     */
    public function __construct(
        /**
         * Algemene formaatclassificatie.
         */
        public string $sizeKey,
        public string $sizeLabel,

        /**
         * Teksten voor de resultaatpagina.
         */
        public string $headline,
        public string $description,

        /**
         * Welk type tas het beste aansluit bij dit profiel.
         */
        public RecommendedBagCategory $recommendedCategory,

        /**
         * Voorkeursdraagwijze voor de matcher.
         */
        public ?CarryMethod $preferredCarryMethod,

        /**
         * USP's die op de resultaatpagina kunnen worden getoond.
         *
         * Bijvoorbeeld:
         * - Laptopvak
         * - Geschikt voor A4
         * - Extra georganiseerd
         * - Compact formaat
         *
         * @var list<string>
         */
        public array $features,
    ) {
    }
}