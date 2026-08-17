<?php

declare(strict_types=1);

namespace App\StyleGuide\Service;

use App\StyleGuide\Entity\StyleGuideAnswer;

final class MaterialPreferenceMapper
{
    /**
     * @return list<string>
     */
    public function getMaterialSlugs(
        StyleGuideAnswer $preference,
    ): array {
        return match ($preference->getCode()) {
            'leer' => [
                'leer',
                'leer-nylon',
            ],

            'vegan' => [
                'polyurethaan',
            ],

            'canvas' => [
                'katoen',
                'katoen-polyester',
            ],

            'nylon' => [
                'nylon',
                'leer-nylon',
            ],

            'geen-voorkeur' => [],

            default => [],
        };
    }

    public function isStrict(
        StyleGuideAnswer $preference,
    ): bool {
        return match ($preference->getCode()) {
            'leer',
            'vegan' => true,

            default => false,
        };
    }
}