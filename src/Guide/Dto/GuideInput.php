<?php

namespace App\Guide\Dto;

final class GuideInput
{
    public function __construct(
        public readonly string $travelTypeSlug,
        public readonly string $transportSlug,
        public readonly string $durationBandSlug,
        public readonly ?string $airlineSlug = null,
        public readonly ?string $ticketSlug = null,

        // Nieuw: frequentie (rare|medium|frequent)
        public readonly ?string $travelFrequency = null,

        // Nieuw: own|rent|either
        public readonly ?string $ownership = null,

        // Laptop
        public readonly ?bool $needsLaptop = null,
        public readonly ?float $laptopMinInch = null,

        // Materiaal-voorkeur (light|premium|max_protection|none)
        public readonly ?string $materialPreference = null,

        // Budget
        public readonly ?int $budgetMin = null,
        public readonly ?int $budgetMax = null,
    ) {}
}