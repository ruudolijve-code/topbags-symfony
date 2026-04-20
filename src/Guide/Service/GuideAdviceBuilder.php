<?php

namespace App\Guide\Service;

use App\Guide\Dto\GuideInput;
use App\Guide\Dto\AdviceResult;
use App\Guide\Entity\TravelProfile;

final class GuideAdviceBuilder
{
    public function __construct(
        private TravelVolumeAdvisor $volumeAdvisor
    ) {}

    /**
     * @param array{personal: array, cabin: array, hold: array} $rulesByScope
     */
    public function build(
        GuideInput $in,
        ?TravelProfile $profile,
        array $rulesByScope
    ): AdviceResult {

        /* =========================================
         * 1️⃣ Allowed scopes
         * ========================================= */

        $allowedScopes = ['cabin']; // veilige default

        if (
            $in->transportSlug === 'plane' &&
            (
                !empty($rulesByScope['personal']) ||
                !empty($rulesByScope['cabin']) ||
                !empty($rulesByScope['hold'])
            )
        ) {
            $allowedScopes = [];

            foreach (['personal', 'cabin', 'hold'] as $scope) {
                if (!empty($rulesByScope[$scope])) {
                    $allowedScopes[] = $scope;
                }
            }
        }

        /* =========================================
         * 2️⃣ Size constraints (MVP = eerste regel)
         * ========================================= */

        $sizeConstraints = [];

        foreach (['personal', 'cabin', 'hold'] as $scope) {

            $sizeConstraints[$scope] = null;

            if (empty($rulesByScope[$scope])) {
                continue;
            }

            $rule = $rulesByScope[$scope][0];

            $sizeConstraints[$scope] = [
                'dimensionType' => $rule->getDimensionType(),
                'maxHeightCm'   => $rule->getMaxHeightCm(),
                'maxWidthCm'    => $rule->getMaxWidthCm(),
                'maxDepthCm'    => $rule->getMaxDepthCm(),
                'maxLinearCm'   => $rule->getMaxLinearCm(),
                'maxWeightKg'   => $rule->getMaxWeightKg(),
            ];
        }

        /* =========================================
         * 3️⃣ Volume range (via advisor, NIET via profile)
         * ========================================= */

        $volumeRange = null;

        if ($in->durationBandSlug !== null) {
            $volumeRange = $this->volumeAdvisor->advise(
                $in->durationBandSlug,
                $in->travelTypeSlug
            );
        }

        /* =========================================
         * 4️⃣ Confidence level
         * ========================================= */

        $confidenceLevel = match ($in->travelFrequency) {
            'frequent'   => 'high',
            'yearly'     => 'medium',
            'rare'       => 'low',
            default      => 'medium',
        };

        /* =========================================
         * 5️⃣ Ownership voorkeur (MVP)
         * ========================================= */

        $ownershipPreference = $in->ownership
            ?? ($in->travelFrequency === 'rare' ? 'either' : 'own');

        /* =========================================
         * 6️⃣ Laptop requirement
         * ========================================= */

        $laptopRequired = $in->needsLaptop
            ?? ($in->travelTypeSlug === 'business');

        $laptopMinInch = $in->laptopMinInch;

        /* =========================================
         * 7️⃣ Material preference (later in scorer)
         * ========================================= */

        $materialPreference = null;

        /* =========================================
         * 8️⃣ Explanations
         * ========================================= */

        $explanations = [
            'Advies gebaseerd op reisduur, frequentie en eventuele airline-regels.',
        ];

        return new AdviceResult(
            allowedScopes: $allowedScopes,
            sizeConstraints: $sizeConstraints,
            volumeRange: $volumeRange,
            materialPreference: $materialPreference,
            ownershipPreference: $ownershipPreference,
            confidenceLevel: $confidenceLevel,
            laptopRequired: $laptopRequired,
            laptopMinInch: $laptopMinInch,
            explanations: $explanations
        );
    }
}