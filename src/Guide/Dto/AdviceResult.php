<?php

namespace App\Guide\Dto;

/**
 * Resultaat van de advies-fase (na profiel + airline regels)
 *
 * Dit object bevat uitsluitend domein-advies,
 * GEEN UI-data.
 */
final class AdviceResult
{
    /**
     * @param string[] $allowedScopes
     *        Bijvoorbeeld: ['personal','cabin','hold']
     *
     * @param array<string, null|array{
     *      dimensionType?: string,
     *      maxHeightCm?: int|null,
     *      maxWidthCm?: int|null,
     *      maxDepthCm?: int|null,
     *      maxLinearCm?: int|null,
     *      maxWeightKg?: float|null
     * }> $sizeConstraints
     *
     * @param array{min:int,max:int}|null $volumeRange
     *
     * @param string|null $materialPreference
     *        Bijvoorbeeld: 'flexible', 'rigid', null
     *
     * @param string $ownershipPreference
     *        own | rent | either
     *
     * @param string $confidenceLevel
     *        low | medium | high | very_high
     *
     * @param string[] $explanations
     */
    public function __construct(
        public readonly array $allowedScopes,
        public readonly array $sizeConstraints,
        public readonly ?array $volumeRange,
        public readonly ?string $materialPreference,
        public readonly string $ownershipPreference,
        public readonly string $confidenceLevel,
        public readonly bool $laptopRequired,
        public readonly ?float $laptopMinInch,
        public readonly array $explanations = [],
    ) {}
}