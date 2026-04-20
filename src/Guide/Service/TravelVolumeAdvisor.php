<?php

namespace App\Guide\Service;

class TravelVolumeAdvisor
{
    /**
     * Basisvolume per reisduur (liters per persoon)
     */
    private array $baseVolumes = [
        '1_4'   => ['min' => 20,  'max' => 40,  'label' => 'Handbagage (S)'],
        '5_8'   => ['min' => 40,  'max' => 70,  'label' => 'Medium (M)'],
        '9_14'  => ['min' => 70,  'max' => 90,  'label' => 'Groot (L)'],
        '15_plus'   => ['min' => 90,  'max' => 120, 'label' => 'Extra Groot (XL)'],
    ];

    /**
     * Correctiefactor per type reis
     */
    private array $travelTypeFactor = [
        'sun'        => -0.10, // zonvakantie
        'winter'     =>  0.20, // wintersport
        'camping'    =>  0.30, // kamperen
        'roundtrip'  => -0.15, // rondreis / backpacken
        'business'   => -0.10,
    ];

    /**
     * niet dubbel volume adviseren bij vliegen
     */
    private array $volumeToScope = [
        ['max' => 30, 'scope' => 'personal'],
        ['max' => 45, 'scope' => 'cabin'],
        ['max' => 80, 'scope' => 'hold'],
        ['max' => 999, 'scope' => 'hold'],
    ];

    /**
     * bij vliegen voorkomen dat dubbel volume wordt geadviseerd
     */
    private function determineScope(int $maxVolume): string
    {
        foreach ($this->volumeToScope as $rule) {
            if ($maxVolume <= $rule['max']) {
                return $rule['scope'];
            }
        }

        return 'hold';
    }

    public function advise(
        ?string $duration,
        ?string $travelType = null
    ): ?array {
        if (!$duration || !isset($this->baseVolumes[$duration])) {
            return null;
        }

        $base = $this->baseVolumes[$duration];
        $factor = $this->travelTypeFactor[$travelType] ?? 0;

        $min = (int) round($base['min'] * (1 + $factor));
        $max = (int) round($base['max'] * (1 + $factor));

        return [
            'min'   => $min,
            'max'   => $max,
            'label' => $base['label'],
            'note'  => $this->buildNote($duration, $travelType),

            // 👇 NIEUW – cruciaal
            'recommendedScope' => $this->determineScope($max),
        ];
    }

    private function buildNote(string $duration, ?string $travelType): string
    {
        $note = match ($duration) {
            '1_4'  => 'Geschikt voor korte trips of weekendjes weg',
            '5_8'  => 'Ideaal voor midweek of korte vakanties',
            '9_14' => 'Comfortabel voor 1–2 weken vakantie',
            '15_plus'  => 'Voor lange reizen of uitgebreide vakanties',
            default => '',
        };

        if ($travelType === 'camping') {
            $note .= '. Extra ruimte nodig voor kampeeruitrusting';
        }

        if ($travelType === 'winter') {
            $note .= '. Houd rekening met dikke kleding';
        }

        return $note;
    }
}