<?php

declare(strict_types=1);

namespace App\StyleGuide\Service;

use App\StyleGuide\Entity\StyleGuideAnswer;

final class QualityPreferenceMapper
{
    /**
     * @return list<string>
     */
    public function getPreferredSegments(
        StyleGuideAnswer $preference,
    ): array {
        return match ($preference->getCode()) {
            'aantrekkelijke-prijs' => [
                'budget',
                'value',
            ],

            'goede-kwaliteit' => [
                'value',
                'premium',
            ],

            'premium' => [
                'premium',
                'luxury',
            ],

            'luxe' => [
                'luxury',
            ],

            'geen-voorkeur' => [],

            default => [],
        };
    }

    public function hasPreference(
        StyleGuideAnswer $preference,
    ): bool {
        return $preference->getCode() !== 'geen-voorkeur';
    }
}