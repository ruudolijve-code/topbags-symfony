<?php

namespace App\Catalog\Service\Ranking;

use App\Guide\Dto\GuideInput;
use App\Guide\Dto\AdviceResult;
use App\Catalog\Entity\Product;
use App\Catalog\Entity\ProductVariant;
use App\Guide\Entity\TravelProfile;

final class ProductScorer
{
    public function score(
        Product $p,
        ProductVariant $v,
        AdviceResult $advice,
        ?TravelProfile $profile,
        GuideInput $in
    ): array {

        $score   = 0.0;
        $reasons = [];

        // ===============================
        // Basisdata
        // ===============================
        $volume       = $p->getVolumeL();
        $material     = $p->getMaterial();
        $materialSlug = $material?->getSlug();
        $wheels       = $p->getWheelsCount() ?? 0;
        $weight       = $p->getWeightKg();
        $price        = $v->getPrice();
        $type         = $p->getLuggageType();

        $isFlexible = $material?->isFlexible() ?? false;
        $isRigid    = $material?->isRigid() ?? false;

        $isDuffle   = $type === 'duffle';
        $isBackpack = $type === 'backpack';
        $isHardcase = $type === 'hardcase';
        $isSoftcase = $type === 'softcase';

        /* =====================================
         * 1️⃣ TRANSPORT DOMINANTIE
         * ===================================== */

        switch ($in->transportSlug) {

            /* ================= TRAIN / BUS ================= */
            case 'train':
            case 'bus':

                if ($wheels >= 4) {
                    $score += 40;
                    $reasons[] = 'Maximale wendbaarheid bij overstappen';
                } elseif ($wheels >= 2) {
                    $score += 25;
                    $reasons[] = 'Handig op perrons en in stations';
                } else {
                    $score -= 30;
                }

                if ($weight !== null && $weight <= 3.0) {
                    $score += 10;
                }

                if ($isRigid) {
                    $score -= 8;
                }

                break;


            /* ================= AUTO ================= */
            case 'car':

                if ($isDuffle && $wheels === 0) {
                    $score += 35;
                    $reasons[] = 'Ideaal voor flexibel inpakken in de kofferbak';
                }

                if ($isFlexible) {
                    $score += 20;
                }

                if ($wheels >= 4 && $isHardcase) {
                    $score -= 10;
                }

                break;


            /* ================= VLIEGTUIG ================= */
            case 'plane':

                if ($weight !== null && $weight <= 2.5) {
                    $score += 20;
                    $reasons[] = 'Lichtgewicht voor vliegen';
                }

                if ($wheels >= 4) {
                    $score += 15;
                    $reasons[] = 'Wendbaar op luchthavens';
                } elseif ($wheels === 0) {
                    $score -= 15;
                }

                break;
        }

        /* =====================================
         * 2️⃣ VOLUME FIT
         * ===================================== */

        if ($advice->volumeRange && $volume !== null) {

            $min = $advice->volumeRange['min'];
            $max = $advice->volumeRange['max'];

            if ($volume >= $min && $volume <= $max) {
                $score += 30;
                $reasons[] = 'Ideale inhoud voor jouw reisduur';
            } elseif ($volume < $min) {
                $score -= 10;
            } elseif ($volume > $max + 20) {
                $score -= 5;
            }
        }

        /* =====================================
         * 3️⃣ TRAVEL TYPE NUANCE
         * ===================================== */

        if ($in->travelTypeSlug === 'roundtrip' && $volume !== null && $volume <= 60) {
            $score += 10;
        }

        /* =====================================
         * 4️⃣ MATERIAAL / PREFERENCE
         * ===================================== */

        switch ($in->materialPreference) {

            case 'robust':

                $score += match ($materialSlug) {
                    'aluminium' => 35,
                    'roxkin-polypropyleen' => 28,
                    'polycarbonaat' => 22,
                    'polypropyleen' => 15,
                    default => 0
                };

                $reasons[] = 'Stevig en duurzaam materiaal';
                break;


            case 'design':

                $score += match ($materialSlug) {
                    'aluminium' => 40,
                    'roxkin-polypropyleen' => 30,
                    'polycarbonaat' => 25,
                    default => 0
                };

                if ($price !== null) {
                    if ($price >= 250) {
                        $score += 20;
                    } elseif ($price >= 200) {
                        $score += 12;
                    }
                }

                $reasons[] = 'Premium uitstraling';
                break;


            case 'lightweight':

                if ($weight !== null) {
                    $score += max(0, 30 - ($weight * 6));
                }

                $reasons[] = 'Lichtgewicht voorkeur';
                break;


            case 'value':

                if ($price !== null) {
                    if ($price <= 120) {
                        $score += 30;
                    } elseif ($price <= 150) {
                        $score += 15;
                    }
                }

                $reasons[] = 'Goede prijs/kwaliteit';
                break;
        }

        /* =====================================
         * 5️⃣ TRAVEL FREQUENCY HERWEGING
         * ===================================== */

        switch ($in->travelFrequency) {

            case 'incidental':

                if ($price !== null) {
                    $score += max(0, 25 - ($price / 20));
                }

                break;


            case 'yearly':

                $score += 8;
                break;


            case 'frequent':

                $score += match ($materialSlug) {
                    'aluminium' => 30,
                    'roxkin-polypropyleen' => 25,
                    'polycarbonaat' => 18,
                    default => 0
                };

                if ($weight !== null && $weight <= 3.0) {
                    $score += 10;
                }

                if ($price !== null && $price < 120) {
                    $score -= 15;
                }

                break;
        }

        return [
            'score'   => round($score, 2),
            'reasons' => array_unique($reasons)
        ];
    }
}