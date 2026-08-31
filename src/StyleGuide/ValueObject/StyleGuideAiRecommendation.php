<?php

declare(strict_types=1);

namespace App\StyleGuide\ValueObject;

final readonly class StyleGuideAiRecommendation
{
    /**
     * @param list<ProductMatch> $matches
     * @param array<int, string> $reasonsByProductId
     */
    public function __construct(
        public array $matches,
        public ?string $summary = null,
        public array $reasonsByProductId = [],
        public bool $isAiGenerated = false,
    ) {
    }

    public function reasonFor(?int $productId): ?string
    {
        return $productId !== null ? ($this->reasonsByProductId[$productId] ?? null) : null;
    }
}
