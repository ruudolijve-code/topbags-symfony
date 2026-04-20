<?php

namespace App\Catalog\Service\Product;

use App\Entity\Product;
use App\Service\Guide\BaggageProfile;

final class ProductComparator
{
    /**
     * @param array<int, array{product: Product, score: int}> $ranked
     * @return string[]  Vergelijkingszinnen
     */
    public function explainTop(array $ranked, BaggageProfile $profile, string $scope): array
    {
        if (count($ranked) < 2) {
            return [];
        }

        $top        = $ranked[0]['product'];
        $alternates = array_slice($ranked, 1, 3);

        $reasons = [];

        /* =========================
         * Gewicht / lichtgewicht
         * ========================= */
        $topWpl = $top->getWeightPerLiter();

        if ($topWpl !== null) {
            $lighterThan = 0;

            foreach ($alternates as $row) {
                $altWpl = $row['product']->getWeightPerLiter();
                if ($altWpl !== null && $topWpl < $altWpl) {
                    $lighterThan++;
                }
            }

            if ($lighterThan > 0) {
                $reasons[] = sprintf(
                    'Lichter dan %d vergelijkbare optie%s',
                    $lighterThan,
                    $lighterThan === 1 ? '' : 's'
                );
            }
        }

        /* =========================
         * Prijs-vergelijking
         * ========================= */
        $topPrice = $top->getMasterVariant()?->getPrice();

        if ($topPrice !== null) {
            $cheaperThan = 0;

            foreach ($alternates as $row) {
                $altPrice = $row['product']->getMasterVariant()?->getPrice();
                if ($altPrice !== null && $topPrice < $altPrice) {
                    $cheaperThan++;
                }
            }

            if ($cheaperThan > 0) {
                $reasons[] = sprintf(
                    'Voordeliger dan %d alternatief binnen deze selectie',
                    $cheaperThan
                );
            }
        }

        /* =========================
         * Volume-efficiëntie
         * ========================= */
        if ($scope !== 'personal' && $top->getVolumeL() !== null) {
            $betterVolume = 0;

            foreach ($alternates as $row) {
                $alt = $row['product'];
                if (
                    $alt->getVolumeL() !== null &&
                    $top->getVolumeL() > $alt->getVolumeL()
                ) {
                    $betterVolume++;
                }
            }

            if ($betterVolume > 0) {
                $reasons[] = 'Meer inhoud bij vergelijkbaar formaat';
            }
        }

        /* =========================
         * Compliance (vliegen)
         * ========================= */
        if ($profile->isPlane() && in_array($scope, ['personal', 'cabin'], true)) {
            $reasons[] = 'Geeft de meeste zekerheid bij controle aan de gate';
        }

        return array_slice($reasons, 0, 3);
    }
}