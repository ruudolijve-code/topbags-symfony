<?php

namespace App\Guide\Service;

final class ArchetypeResolver
{
    public function resolve(?string $travelType, ?string $transport): string
    {
        $travelType = $travelType ?? 'general';
        $transport  = $transport ?? 'car';

        // ===============================
        // 1️⃣ Transport-realiteit eerst
        // ===============================
        if ($transport === 'car') {
            return 'roadtrip_comforter';
        }

        // ===============================
        // 2️⃣ Basis op reissoort
        // ===============================
        return match ($travelType) {

            'citytrip', 'weekend'
                => 'weekend_explorer',

            'business'
                => 'business_minimalist',

            'vacation', 'zomervakantie'
                => 'sun_seeker',

            'winter', 'wintersport'
                => 'winter_warrior',

            'roundtrip', 'rondreis',
            'backpacking', 'backpack'
                => 'backpack_nomad',

            default
                => 'practical_traveler',
        };
    }
}