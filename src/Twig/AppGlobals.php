<?php

namespace App\Twig;

use Twig\Extension\GlobalsInterface;

final class AppGlobals implements GlobalsInterface
{
    public function __construct(
        private string $ga4MeasurementId
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'ga4_measurement_id' => $this->ga4MeasurementId,
        ];
    }
}