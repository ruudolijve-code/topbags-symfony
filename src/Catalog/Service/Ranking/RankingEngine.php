<?php

namespace App\Catalog\Service\Ranking;

use App\Catalog\Service\VariantImagePathResolver;
use App\Guide\Dto\AdviceOutput;
use App\Guide\Dto\AdviceResult;
use App\Guide\Dto\GuideInput;
use App\Guide\Dto\RankedItem;
use App\Guide\Entity\TravelProfile;

final class RankingEngine
{
    public function __construct(
        private ProductScorer $scorer,
        private VariantImagePathResolver $variantImagePathResolver,
    ) {
    }

    /**
     * @param array<string, array<\App\Catalog\Entity\Product>> $productsByScope
     */
    public function rank(
        array $productsByScope,
        AdviceResult $advice,
        ?TravelProfile $profile,
        GuideInput $in
    ): AdviceOutput {
        $ranked = [];

        foreach ($productsByScope as $scope => $products) {
            $ranked[$scope] = [];

            if ($products === []) {
                continue;
            }

            foreach ($products as $product) {
                $variant = $product->getMasterVariant();

                if ($variant === null) {
                    continue;
                }

                $result = $this->scorer->score($product, $variant, $advice, $profile, $in);

                $ranked[$scope][] = new RankedItem(
                    product: $product,
                    variant: $variant,
                    score: (float) ($result['score'] ?? 0.0),
                    reasons: (array) ($result['reasons'] ?? []),
                    mediaPath: $this->variantImagePathResolver->fromVariant($variant)
                );
            }

            if ($ranked[$scope] !== []) {
                usort(
                    $ranked[$scope],
                    static fn (RankedItem $a, RankedItem $b): int => $b->score <=> $a->score
                );
            }
        }

        return new AdviceOutput(
            input: $in,
            advice: $advice,
            rankedByScope: $ranked,
            messages: []
        );
    }
}