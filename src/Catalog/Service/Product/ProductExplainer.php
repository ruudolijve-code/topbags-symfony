<?php

namespace App\Catalog\Service\Product;

use App\Entity\Product;
use App\Service\Guide\BaggageProfile;
use App\Service\Guide\LightweightClassifier;


final class ProductExplainer
{
    public function __construct(
        private LightweightClassifier $lightweightClassifier
    ) {}
    /**
     * @param array<int, array{product: Product, score: int}> $ranked
     * @return array<int, array{
     *     product: Product,
     *     score: int,
     *     isBest: bool,
     *     labels: string[],
     *     reasons: string[]
     * }>
     */
    public function explain(array $ranked, BaggageProfile $profile): array
    {
        $explained = [];

        $topScore = $ranked[0]['score'] ?? null;

        foreach ($ranked as $index => $row) {
            $product = $row['product'];
            $score   = $row['score'];

            $isBest = ($index === 0 && $score === $topScore);

            $explained[] = [
                'product' => $product,
                'score'   => $score,
                'isBest'  => $isBest,
                'labels'  => $this->buildLabels($product, $profile, $isBest),
                'reasons' => $this->buildReasons($product, $profile, $isBest),
            ];
        }

        return $explained;
    }

    /* ==========================================================
     * LABELS — kort, badge-waardig
     * ========================================================== */

    private function buildLabels(
        Product $product,
        BaggageProfile $profile,
        bool $isBest
    ): array {
        $labels = [];

        /* ⭐ Beste keuze */
        if ($isBest) {
            $labels[] = 'Beste keuze';
        }

        /* 🎒 Lichtgewicht label */
        if (
            $profile->maxLightweightClass !== null &&
            $product->getLightweightClass() !== null
        ) {
            $productClass = $product->getLightweightClass();
            $maxClass     = $profile->maxLightweightClass;

            if (
                $this->lightweightClassifier->rank($productClass)
                <=
                $this->lightweightClassifier->rank($maxClass)
            ) {
                $labels[] = $product->getLightweightLabel();
            }
        }

        /* 🧱 Materiaal */
        if (
            $product->getMaterial() !== null &&
            in_array(
                $product->getMaterial()->getSlug(),
                $profile->materials,
                true
            )
        ) {
            $labels[] = ucfirst($product->getMaterial()->getName());
        }

        /* 💰 Prijs-kwaliteit */
        if (
            in_array($profile->preference, ['price', 'value'], true) &&
            in_array($profile->frequency, ['incidental', 'yearly'], true)
        ) {
            $labels[] = 'Goede prijs-kwaliteit';
        }

        /* ✈️ Regels / compliance */
        if ($profile->priority === 'compliance') {
            $labels[] = 'Voldoet aan airline-regels';
        }

        return array_values(array_unique($labels));
    }

    /* ==========================================================
     * REDENEN — mensentaal, contextbewust
     * ========================================================== */

    private function buildReasons(
        Product $product,
        BaggageProfile $profile,
        bool $isBest
    ): array {
        $reasons = [];

        if ($isBest) {
            $reasons[] = 'Dit is de beste match op basis van jouw reis en voorkeuren';
        }

        /* 📦 Volume-context (alleen uitleg, geen filter) */
        if (
            $profile->volume !== null &&
            $product->getVolumeL() !== null &&
            $product->getVolumeL() >= $profile->volume['min'] &&
            $product->getVolumeL() <= $profile->volume['max']
        ) {
            $reasons[] = 'Volume sluit goed aan bij de duur van jouw reis';
        }

        /* 🚉 Transport-context */
        switch ($profile->transport) {
            case 'plane':
                if ($product->isCabinSize()) {
                    $reasons[] = 'Geschikt als handbagage volgens airline-afmetingen';
                }
                if ($product->isUnderseater()) {
                    $reasons[] = 'Kan onder de stoel als personal item';
                }
                break;

            case 'train':
                $reasons[] = 'Praktisch en wendbaar voor reizen met de trein';
                break;

            case 'car':
                $reasons[] = 'Makkelijk en flexibel in te laden in de auto';
                break;

            case 'bus':
                $reasons[] = 'Compact formaat, prettig voor busreizen';
                break;
        }

        /* 🛞 Comfort */
        if (
            $profile->priority === 'comfort' &&
            ($product->getWheelsCount() ?? 0) >= 4
        ) {
            $reasons[] = 'Vier wielen zorgen voor comfortabel vervoer';
        }

        /* 🔐 Duurzaamheid / veiligheid */
        if (
            $profile->priority === 'durability' &&
            $product->isTsaLock()
        ) {
            $reasons[] = 'Extra veilig dankzij TSA-slot';
        }

        if ($reasons === []) {
            $reasons[] = 'Past goed bij jouw reisprofiel';
        }

        return $reasons;
    }

    /* ==========================================================
     * HULPFUNCTIE — gewichtsklasse rangorde
     * ========================================================== */

    private function lightweightRank(string $class): int
    {
        return match ($class) {
            'ultra_light' => 1,
            'light'       => 2,
            'normal'      => 3,
            default       => 99,
        };
    }
}