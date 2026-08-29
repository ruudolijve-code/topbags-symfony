<?php

declare(strict_types=1);

namespace App\StyleGuide\Service;

use App\Catalog\Entity\Product;
use App\StyleGuide\Entity\StyleGuideAffinity;
use App\StyleGuide\Repository\StyleGuideAffinityRepository;
use App\StyleGuide\Repository\StyleGuideProductOverrideRepository;
use App\StyleGuide\ValueObject\StyleAffinityResult;
use App\StyleGuide\ValueObject\StyleGuideCriteria;

final class StyleAffinityCalculator
{
    public function __construct(private readonly StyleGuideAffinityRepository $affinities, private readonly StyleGuideProductOverrideRepository $overrides, private readonly ProductStyleAttributeResolver $attributes) {}

    public function calculate(Product $product, StyleGuideCriteria $criteria): StyleAffinityResult
    {
        $colors = $this->attributes->colors($product);
        $families = array_unique(array_filter(array_map(static fn ($color): ?string => $color->getFamily() !== null ? strtolower(trim($color->getFamily())) : null, $colors)));
        $score = 0;
        $reasons = [];
        foreach ($this->affinities->findActiveForWorld($criteria->styleWorld) as $affinity) {
            if (!$this->matches($affinity, $product, $colors, $families)) { continue; }
            $score += $affinity->getScore();
            if ($affinity->getReason() !== null && $affinity->getScore() > 0) { $reasons[] = $affinity->getReason(); }
        }
        $override = $this->overrides->findActive($product, $criteria->styleWorld);
        $overrideScore = $override?->getScoreAdjustment() ?? 0;
        if ($override?->getReason() !== null && $overrideScore > 0) { $reasons[] = $override->getReason(); }
        return new StyleAffinityResult($score, $overrideScore, array_values(array_unique($reasons)));
    }

    /** @param list<\App\Catalog\Entity\Color> $colors @param list<string> $families */
    private function matches(StyleGuideAffinity $affinity, Product $product, array $colors, array $families): bool
    {
        if ($affinity->getBrand() !== null) { return $product->getBrand() === $affinity->getBrand(); }
        if ($affinity->getMaterial() !== null) { return $product->getMaterial() === $affinity->getMaterial(); }
        if ($affinity->getColor() !== null) { return in_array($affinity->getColor(), $colors, true); }
        if ($affinity->getCategory() !== null) { return $product->getCategories()->contains($affinity->getCategory()); }
        return $affinity->getColorFamily() !== null && in_array(strtolower($affinity->getColorFamily()), $families, true);
    }
}
