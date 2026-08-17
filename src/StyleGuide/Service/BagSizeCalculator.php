<?php

declare(strict_types=1);

namespace App\StyleGuide\Service;

use App\StyleGuide\Enum\BagSizePreference;
use App\StyleGuide\Enum\CarryItem;

final class BagSizeCalculator
{
    /**
     * @param list<CarryItem> $items
     */
    public function calculate(array $items): BagSizePreference
    {
        if ($items === []) {
            return BagSizePreference::COMPACT;
        }

        if (in_array(CarryItem::MANY_ITEMS, $items, true)) {
            return BagSizePreference::SPACIOUS;
        }

        if (
            in_array(CarryItem::LAPTOP, $items, true)
            || in_array(CarryItem::A4_DOCUMENTS, $items, true)
        ) {
            return BagSizePreference::LARGE;
        }

        $highestScore = max(
            array_map(
                static fn (CarryItem $item): int => $item->sizeScore(),
                $items
            )
        );

        if ($highestScore >= 2 || count($items) >= 3) {
            return BagSizePreference::MEDIUM;
        }

        return BagSizePreference::COMPACT;
    }
}