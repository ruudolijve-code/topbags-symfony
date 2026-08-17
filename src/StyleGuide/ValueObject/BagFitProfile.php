<?php

declare(strict_types=1);

namespace App\StyleGuide\ValueObject;

final readonly class BagFitProfile
{
    public function __construct(
        public float $minimumWidthCm,
        public float $minimumHeightCm,
        public float $minimumDepthCm,
        public float $minimumVolumeL,

        public bool $requiresLaptopCompartment,
        public ?float $requiredLaptopInch,

        public bool $requiresA4Fit,

        public bool $hasFlatLoad,
        public bool $hasBulkyLoad,
        public bool $hasVerticalLoad,

        public int $objectCount,
    ) {
    }

    public function hasLaptopRequirement(): bool
    {
        return $this->requiresLaptopCompartment
            || $this->requiredLaptopInch !== null;
    }
}