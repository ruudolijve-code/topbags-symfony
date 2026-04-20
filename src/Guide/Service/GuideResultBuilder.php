<?php

namespace App\Guide\Service;

use App\Repository\TransportAdviceRepository;

final class GuideResultBuilder
{
    public function __construct(
        private TravelVolumeAdvisor $volumeAdvisor,
        private BaggageProfileBuilder $baggageProfileBuilder,
    ) {}

    /**
     * Bouwt uitsluitend het domeinprofiel.
     * GEEN UI-array.
     */
    public function build(
        array $input,
        array $ticketSummary
    ): BaggageProfile {

        /* ==================================================
         * 1️⃣ Input normaliseren
         * ================================================== */
        $input = $this->normalizeInput($input);

        /* ==================================================
         * 2️⃣ Allowed baggage scopes (plane-only relevant)
         * ================================================== */
        $isPlane = ($input['transport'] === 'plane');

        if ($isPlane) {

            $allowed = [
                'personal' => (bool) ($ticketSummary['personal']['allowed'] ?? false),
                'cabin'    => (bool) ($ticketSummary['cabin']['allowed'] ?? false),
                'hold'     => (bool) ($ticketSummary['hold']['allowed'] ?? false),
            ];

        } else {

            $allowed = [
                'main'     => true,
                'personal' => true, // altijd mogelijk
            ];
        }

        /* ==================================================
         * 3️⃣ Volume advies (domeinregel)
         * ================================================== */
        $volume = $this->volumeAdvisor->advise(
            $input['duration'] ?? null,
            $input['travel_type'] ?? null
        );

        /* ==================================================
         * 4️⃣ Centrale waarheid → BaggageProfile
         * ================================================== */
        return $this->baggageProfileBuilder->build(
            input:   $input,
            allowed: $allowed,
            volume:  $volume
        );
    }

    /* ==================================================
     * 🔐 Centrale input-normalisatie
     * ================================================== */
    private function normalizeInput(array $input): array
    {
        return array_merge([
            'travel_type' => 'general',
            'transport'   => 'plane',
            'airline'     => null,
            'ticket'      => null,
            'duration'    => null,
            'frequency'   => null,
            'preferences' => null,
        ], $input);
    }
}