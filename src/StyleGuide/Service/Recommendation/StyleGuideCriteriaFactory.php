<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Recommendation;

use App\StyleGuide\Entity\StyleGuideAnswer;
use App\StyleGuide\Entity\StyleGuideWorld;
use App\StyleGuide\ValueObject\StyleGuideCriteria;

final class StyleGuideCriteriaFactory
{
    /**
     * @param list<StyleGuideAnswer> $carryItems
     */
    public function create(
        StyleGuideAnswer $targetAudience,
        StyleGuideAnswer $useMoment,
        StyleGuideWorld $styleWorld,
        StyleGuideAnswer $outfitPreference,
        array $carryItems,
        StyleGuideAnswer $carryMethod,
        StyleGuideAnswer $materialPreference,
        StyleGuideAnswer $budgetPreference,
    ): StyleGuideCriteria {
        return new StyleGuideCriteria(
            targetAudience: $targetAudience,
            useMoment: $useMoment,
            styleWorld: $styleWorld,
            outfitPreference: $outfitPreference,
            carryItems: $carryItems,
            carryMethod: $carryMethod,
            materialPreference: $materialPreference,
            budgetPreference: $budgetPreference,
        );
    }
}
