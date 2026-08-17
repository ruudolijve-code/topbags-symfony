<?php

declare(strict_types=1);

namespace App\StyleGuide\ValueObject;

use App\StyleGuide\Entity\StyleGuideAnswer;
use App\StyleGuide\Entity\StyleGuideWorld;

final readonly class StyleGuideCriteria
{
    /**
     * @param list<StyleGuideAnswer> $carryItems
     */
    public function __construct(
        public StyleGuideAnswer $useMoment,
        public StyleGuideWorld $styleWorld,
        public StyleGuideAnswer $outfitPreference,
        public array $carryItems,
        public StyleGuideAnswer $carryMethod,
        public StyleGuideAnswer $materialPreference,
        public StyleGuideAnswer $budgetPreference,
    ) {
    }

    public function hasCarryItem(string $code): bool
    {
        foreach ($this->carryItems as $carryItem) {
            if ($carryItem->getCode() === $code) {
                return true;
            }
        }

        return false;
    }

    public function hasLaptop(): bool
    {
        return $this->hasCarryItem('laptop');
    }
}