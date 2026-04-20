<?php

namespace App\Guide\Service;

final class LightweightClassifier
{
    private const ORDER = [
        'ultra_light' => 1,
        'light'       => 2,
        'normal'      => 3,
        'heavy'       => 4,
    ];

    public function classify(int $gPerLiter): string
    {
        return match (true) {
            $gPerLiter <= 60 => 'ultra_light',
            $gPerLiter <= 75 => 'light',
            $gPerLiter <= 95 => 'normal',
            default          => 'heavy',
        };
    }

    public function rank(string $class): int
    {
        return self::ORDER[$class] ?? 99;
    }

    /**
     * Geef maximale toegestane class terug als string
     * bijv. lightweight → 'light'
     */
    public function maxAllowed(string $target): ?string
    {
        return match ($target) {
            'light' => 'light',
            default => null,
        };
    }
}