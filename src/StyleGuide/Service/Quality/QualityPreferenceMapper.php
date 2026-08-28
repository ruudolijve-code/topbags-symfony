<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Quality;

use App\StyleGuide\Entity\StyleGuideAnswer;

final class QualityPreferenceMapper
{
    public const SEGMENT_BUDGET = 'budget';
    public const SEGMENT_VALUE = 'value';
    public const SEGMENT_PREMIUM = 'premium';
    public const SEGMENT_LUXURY = 'luxury';

    public const NO_PREFERENCE = 'geen-voorkeur';

    /**
     * @return list<string>
     */
    public function getPreferredSegments(
        StyleGuideAnswer $preference,
    ): array {
        return match ($preference->getCode()) {

            'aantrekkelijke-prijs' => [
                self::SEGMENT_BUDGET,
                self::SEGMENT_VALUE,
            ],

            'goede-kwaliteit' => [
                self::SEGMENT_VALUE,
                self::SEGMENT_PREMIUM,
            ],

            'premium' => [
                self::SEGMENT_PREMIUM,
                self::SEGMENT_LUXURY,
            ],

            'luxe' => [
                self::SEGMENT_LUXURY,
            ],

            self::NO_PREFERENCE => [],

            default => [],
        };
    }

    public function hasPreference(
        StyleGuideAnswer $preference,
    ): bool {
        return $preference->getCode() !== self::NO_PREFERENCE;
    }

    public function prefersSegment(
        StyleGuideAnswer $preference,
        string $segment,
    ): bool {
        return in_array(
            $segment,
            $this->getPreferredSegments($preference),
            true,
        );
    }
}
