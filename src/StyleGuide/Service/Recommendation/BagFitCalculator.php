<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Recommendation;

use App\StyleGuide\Entity\StyleGuideAnswer;
use App\StyleGuide\Entity\StyleGuideAnswerObjectProfile;
use App\StyleGuide\Entity\StyleGuideObjectProfile;
use App\StyleGuide\Repository\StyleGuideAnswerObjectProfileRepository;
use App\StyleGuide\ValueObject\BagFitProfile;

final class BagFitCalculator
{
    private const PACKING_VOLUME_FACTOR = 1.15;

    public function __construct(
        private readonly StyleGuideAnswerObjectProfileRepository $mappingRepository,
    ) {
    }

    /**
     * @param list<StyleGuideAnswer> $answers
     */
    public function calculate(array $answers): BagFitProfile
    {
        $answerIds = [];

        foreach ($answers as $answer) {
            $id = $answer->getId();

            if ($id !== null) {
                $answerIds[] = $id;
            }
        }

        $mappings = $this->mappingRepository->findActiveForAnswerIds(
            $answerIds,
        );

        return $this->calculateFromMappings($mappings);
    }

    /**
     * @param list<StyleGuideAnswerObjectProfile> $mappings
     */
    private function calculateFromMappings(
        array $mappings,
    ): BagFitProfile {
        $minimumWidthCm = 0.0;
        $minimumHeightCm = 0.0;

        $depthContribution = 0.0;
        $largestSingleDepth = 0.0;

        $calculatedVolumeL = 0.0;

        $requiresLaptopCompartment = false;
        $requiredLaptopInch = null;
        $requiresA4Fit = false;

        $hasFlatLoad = false;
        $hasBulkyLoad = false;
        $hasVerticalLoad = false;

        $objectCount = 0;

        foreach ($mappings as $mapping) {
            if (!$mapping->isActive()) {
                continue;
            }

            $profile = $mapping->getObjectProfile();

            if (
                !$profile instanceof StyleGuideObjectProfile
                || !$profile->isActive()
            ) {
                continue;
            }

            ++$objectCount;

            $width = $this->requiredWidth($profile);
            $height = $this->requiredHeight($profile);
            $depth = $this->requiredDepth($profile);

            $minimumWidthCm = max(
                $minimumWidthCm,
                $width,
            );

            $minimumHeightCm = max(
                $minimumHeightCm,
                $height,
            );

            $largestSingleDepth = max(
                $largestSingleDepth,
                $depth,
            );

            $depthContribution +=
                $depth * $this->depthFactor($profile);

            $calculatedVolumeL +=
                $this->calculateObjectVolume($profile);

            if ($profile->requiresLaptopCompartment()) {
                $requiresLaptopCompartment = true;
            }

            if ($profile->getRequiredLaptopInch() !== null) {
                $requiredLaptopInch = max(
                    $requiredLaptopInch ?? 0.0,
                    $profile->getRequiredLaptopInch(),
                );
            }

            if ($profile->requiresA4Fit()) {
                $requiresA4Fit = true;
            }

            match ($profile->getShapeType()) {
                'flat' => $hasFlatLoad = true,
                'bulky' => $hasBulkyLoad = true,
                default => null,
            };

            if ($profile->getOrientation() === 'vertical') {
                $hasVerticalLoad = true;
            }
        }

        $minimumDepthCm = max(
            $largestSingleDepth,
            $depthContribution,
        );

        $minimumVolumeL =
            $calculatedVolumeL * self::PACKING_VOLUME_FACTOR;

        return new BagFitProfile(
            minimumWidthCm: round($minimumWidthCm, 1),
            minimumHeightCm: round($minimumHeightCm, 1),
            minimumDepthCm: round($minimumDepthCm, 1),
            minimumVolumeL: round($minimumVolumeL, 1),

            requiresLaptopCompartment: $requiresLaptopCompartment,
            requiredLaptopInch: $requiredLaptopInch,

            requiresA4Fit: $requiresA4Fit,

            hasFlatLoad: $hasFlatLoad,
            hasBulkyLoad: $hasBulkyLoad,
            hasVerticalLoad: $hasVerticalLoad,

            objectCount: $objectCount,
        );
    }

    private function requiredWidth(
        StyleGuideObjectProfile $profile,
    ): float {
        return ($profile->getWidthCm() ?? 0.0)
            + ($profile->getWidthMarginCm() ?? 0.0);
    }

    private function requiredHeight(
        StyleGuideObjectProfile $profile,
    ): float {
        return ($profile->getHeightCm() ?? 0.0)
            + ($profile->getHeightMarginCm() ?? 0.0);
    }

    private function requiredDepth(
        StyleGuideObjectProfile $profile,
    ): float {
        return ($profile->getDepthCm() ?? 0.0)
            + ($profile->getDepthMarginCm() ?? 0.0);
    }

    private function calculateObjectVolume(
        StyleGuideObjectProfile $profile,
    ): float {
        if ($profile->getVolumeL() !== null) {
            return $profile->getVolumeL()
                * $profile->getBulkFactor();
        }

        $width = $this->requiredWidth($profile);
        $height = $this->requiredHeight($profile);
        $depth = $this->requiredDepth($profile);

        if (
            $width <= 0.0
            || $height <= 0.0
            || $depth <= 0.0
        ) {
            return 0.0;
        }

        return (
            ($width * $height * $depth) / 1000
        ) * $profile->getBulkFactor();
    }

    private function depthFactor(
        StyleGuideObjectProfile $profile,
    ): float {
        return match ($profile->getShapeType()) {
            'flat' => 0.55,
            'regular' => 0.80,
            'bulky' => 1.00,
            default => 0.80,
        };
    }
}
