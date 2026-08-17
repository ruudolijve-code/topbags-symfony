<?php

declare(strict_types=1);

namespace App\StyleGuide\Service;

use App\StyleGuide\ValueObject\ProductMatch;

final class StyleGuideProductRanker
{
    private const DEFAULT_LIMIT = 24;

    /**
     * Sorteert de beste productmatches.
     *
     * Bij gelijke score blijft de volgorde stabiel door
     * terug te vallen op het product-id.
     *
     * @param list<ProductMatch> $matches
     *
     * @return list<ProductMatch>
     */
    public function rank(
        array $matches,
        int $limit = self::DEFAULT_LIMIT,
    ): array {
        usort(
            $matches,
            static function (
                ProductMatch $left,
                ProductMatch $right,
            ): int {
                /*
                 * Hoogste score eerst.
                 */
                $comparison =
                    $right->score <=> $left->score;

                if ($comparison !== 0) {
                    return $comparison;
                }

                /*
                 * Gelijke score?
                 * Houd de sortering stabiel.
                 */
                return ($left->product->getId() ?? 0)
                    <=> ($right->product->getId() ?? 0);
            },
        );

        return array_slice(
            $matches,
            0,
            max(1, $limit),
        );
    }
}