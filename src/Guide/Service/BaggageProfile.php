<?php

namespace App\Guide\Service;

final class BaggageProfile
{
    /**
     * @param string|null $transport                plane | car | train | bus
     * @param string[]    $allowedScopes            personal | cabin | hold (alleen bij plane)
     * @param array<string,int[]> $categoryIdsByScope
     *        [
     *          'personal' => [1, 5, 9],
     *          'cabin'    => [10, 11],
     *          'hold'     => [4],
     *          'main'     => [7, 12] // niet-vliegen
     *        ]
     * @param array{min:int,max:int,label?:string}|null $volume
     * @param string[]    $materials                material slugs (filtering)
     */
    public function __construct(
        public readonly ?string $transport,
        public readonly array $allowedScopes,
        public readonly array $categoryIdsByScope,
        public readonly ?array $volume,
        public readonly array $materials,
        public readonly ?string $maxLightweightClass,
        public readonly string $priority,
        public readonly ?string $frequency = null,   // incidental | yearly | frequent
        public readonly ?string $preference = null   // price | value | design | robust
    ) {}

    /* =============================
     * Helpers
     * ============================= */

    public function isPlane(): bool
    {
        return $this->transport === 'plane';
    }

    /**
     * Category IDs voor een specifieke scope
     */
    public function getCategoryIdsForScope(string $scope): array
    {
        return $this->categoryIdsByScope[$scope] ?? [];
    }

    /**
     * Subprofiel voor één bagagescope (alleen data, geen logica)
     */
    public function forScope(string $scope): self
    {
        return new self(
            transport: $this->transport,
            allowedScopes: [$scope],
            categoryIdsByScope: [
                $scope => $this->getCategoryIdsForScope($scope),
            ],
            volume: $this->volume,
            materials: $this->materials,
            maxLightweightClass: $this->maxLightweightClass,
            priority: $this->priority,
            frequency: $this->frequency,
            preference: $this->preference
        );
    }
}