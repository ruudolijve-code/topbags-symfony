<?php

namespace App\Guide\Service;

use App\Guide\Entity\TravelProfile;

final class ProfileFactory
{
    public function make(
        TravelProfile $profile,
        array $guideResult,
        array $query
    ): array {

        $volume = $guideResult['volume'] ?? null;
        $transport = $guideResult['transport'] ?? null;
        $pref = $query['preferences'] ?? null;
        $freq = $query['frequency'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | UX Tone & Microcopy
        |--------------------------------------------------------------------------
        */

        $tonePrefix = $this->tonePrefix($pref);
        $microcopy  = $this->microcopyFromFrequency($freq)
            ?? $profile->getMicrocopy();

        /*
        |--------------------------------------------------------------------------
        | Volume UX
        |--------------------------------------------------------------------------
        */

        $rangeLabel = $volume
            ? sprintf('%s–%s liter', (int)$volume['min'], (int)$volume['max'])
            : 'Afgestemd op jouw reis';

        $scopeLabel = $this->scopeLabel(
            $volume['recommendedScope'] ?? null
        );

        $volumeExplain = $volume['note']
            ?? 'We stemmen dit af op jouw reisduur en type trip.';

                /*
        |--------------------------------------------------------------------------
        | Volume 3-laags meter UX
        |--------------------------------------------------------------------------
        */

        $total = 120;

            $min = $volume['min'] ?? 0;
            $max = $volume['max'] ?? 0;

            $minPercent = round(($min / $total) * 100, 2);
            $maxPercent = round(($max / $total) * 100, 2);
            $rangePercent = max(0, $maxPercent - $minPercent);


        /*
        |--------------------------------------------------------------------------
        | Airline UX
        |--------------------------------------------------------------------------
        */

        $airEnabled = $transport === 'plane'
            && !empty($guideResult['airline']);

        $airBadges = $this->buildAirlineBadges($guideResult, $airEnabled);

        /*
        |--------------------------------------------------------------------------
        | Build response
        |--------------------------------------------------------------------------
        */

        return [
            'title'     => $profile->getTitle(),
            'subtitle'  => $tonePrefix . $profile->getSubtitle(),
            'microcopy' => $microcopy,

            'hero' => [
                'imageUrl' => $profile->getHeroImage(),
                'imageAlt' => $profile->getHeroAlt(),
            ],

            'story' => [
                'intro' => $profile->getStoryIntro(),
                'block' => $profile->getStoryBlock(),
            ],

            'traits' => $this->buildTraits($query, $guideResult),

            'volume' => [
                'rangeLabel' => $rangeLabel,
                'explain'    => $volumeExplain,
                'scopeLabel' => $scopeLabel,
                'min'        => $volume['min'] ?? null,
                'max'        => $volume['max'] ?? null,
                'minPercent' => $minPercent,
                'rangePercent' => $rangePercent,
            ],

            'volumeNarrative' => $this->buildVolumeNarrative($volume ?? [], $query),

            'air' => [
                'enabled' => $airEnabled,
                'badges'  => $airBadges,
                'rules'   => $guideResult['airRules'] ?? null,
            ],

            'bestReasons' => $this->buildBestReasons($profile, $airEnabled),

            'addOns' => [],
            'tips'   => [],

            'faq' => $this->defaultFaq(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | UX TRAITS (natuurlijk taalgebruik)
    |--------------------------------------------------------------------------
    */

    private function buildTraits(array $query, array $result): array
    {
        return [
            [
                'icon'  => '⏱️',
                'label' => 'Reisduur',
                'value' => $this->durationLabel($query['duration'] ?? null),
            ],
            [
                'icon'  => '🧭',
                'label' => 'Type reis',
                'value' => $this->travelTypeLabel($query['travel_type'] ?? null),
            ],
            [
                'icon'  => $this->transportIcon($result['transport'] ?? null),
                'label' => 'Vervoer',
                'value' => $this->transportLabel($result['transport'] ?? null),
            ],
            [
                'icon'  => '🎯',
                'label' => 'Wat jij belangrijk vindt',
                'value' => $this->preferenceLabel($query['preferences'] ?? null),
            ],
        ];
    }

    private function durationLabel(?string $slug): string
    {
        return match ($slug) {
            '1_4'     => '1 tot 4 dagen',
            '5_8'     => '5 tot 8 dagen',
            '9_14'    => '9 tot 14 dagen',
            '15_plus' => '15 dagen of langer',
            default   => 'Afgestemd op jouw planning',
        };
    }

    private function travelTypeLabel(?string $slug): string
    {
        return match ($slug) {
            'weekend'       => 'Weekendje weg',
            'citytrip'      => 'Citytrip',
            'zomervakantie' => 'Zomervakantie',
            'wintersport'   => 'Wintersport',
            'zaken'         => 'Zakenreis',
            'camping'       => 'Kamperen',
            'rondreis'      => 'Rondreis',
            'backpack'      => 'Backpacken',
            default         => 'Persoonlijke reis',
        };
    }

    private function transportLabel(?string $slug): string
    {
        return match ($slug) {
            'plane' => 'Vliegtuig',
            'car'   => 'Auto',
            'train' => 'Trein',
            'bus'   => 'Bus',
            default => 'Flexibel vervoer',
        };
    }

    private function transportIcon(?string $slug): string
    {
        return match ($slug) {
            'plane' => '✈️',
            'car'   => '🚗',
            'train' => '🚆',
            'bus'   => '🚌',
            default => '🌍',
        };
    }

    private function preferenceLabel(?string $slug): string
    {
        return match ($slug) {
            'lightweight'     => 'Zo licht mogelijk reizen',
            'extra_stevig'    => 'Extra bescherming',
            'stijlvol'        => 'Mooie uitstraling',
            'prijs_kwaliteit' => 'Beste prijs/kwaliteit',
            default           => 'Een goede balans',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Tone
    |--------------------------------------------------------------------------
    */

    private function tonePrefix(?string $pref): string
    {
        return match ($pref) {
            'lightweight'     => 'Licht en wendbaar. ',
            'extra_stevig'    => 'Sterk en zorgeloos. ',
            'stijlvol'        => 'Stijlvol gekozen. ',
            'prijs_kwaliteit' => 'Slim besteed. ',
            default           => '',
        };
    }

    private function microcopyFromFrequency(?string $freq): ?string
    {
        return match ($freq) {
            'often'  => 'Je reist vaker: comfort en duurzaamheid wegen extra mee.',
            'yearly' => 'Je reist af en toe: we houden het overzichtelijk en zeker.',
            default  => null,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Airline
    |--------------------------------------------------------------------------
    */

    private function buildAirlineBadges(array $guideResult, bool $enabled): array
    {
        if (!$enabled) {
            return [];
        }

        $badges = [];

        if (!empty($guideResult['airline'])) {
            $badges[] = strtoupper($guideResult['airline']);
        }

        if (!empty($guideResult['ticket'])) {
            $badges[] = ucfirst($guideResult['ticket']) . ' ticket';
        }

        $badges[] = 'Expandable meegenomen';

        return $badges;
    }

    /*
    |--------------------------------------------------------------------------
    | Best reasons
    |--------------------------------------------------------------------------
    */

    private function buildBestReasons(
        TravelProfile $profile,
        bool $airEnabled
    ): array {

        $reasons = [
            'Past bij jouw reisstijl',
            'Logische balans tussen volume en comfort',
        ];

        if ($airEnabled) {
            $reasons[] = 'Gecontroleerd op airline-afmetingen';
        }

        return $reasons;
    }

    /*
    |--------------------------------------------------------------------------
    | Scope UX label
    |--------------------------------------------------------------------------
    */

    private function scopeLabel(?string $scope): ?string
    {
        return match ($scope) {
            'personal' => 'Compact (onder de stoel)',
            'cabin'    => 'Handbagage formaat',
            'hold'     => 'Ruimbagage formaat',
            'main'     => 'Grote koffer',
            default    => null,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | FAQ
    |--------------------------------------------------------------------------
    */

    private function defaultFaq(): array
    {
        return [
            [
                'q' => 'Mag een cabin-koffer ook in het ruim?',
                'a' => 'Ja. Cabin-koffers mogen altijd in het ruim. Andersom niet.',
            ],
            [
                'q' => 'Wat gebeurt er als mijn koffer expandable is?',
                'a' => 'Wij rekenen de maximale inhoud mee bij airline-controle.',
            ],
            [
                'q' => 'Waarom adviseren jullie dit volume?',
                'a' => 'We combineren reisduur, type reis en praktijkervaring.',
            ],
            [
                'q' => 'Wat als mijn airline verandert?',
                'a' => 'Je kunt eenvoudig een andere maatschappij kiezen. We passen de controle direct aan.',
            ],
        ];
    }

     /*
    |--------------------------------------------------------------------------
    | Volume narrative builder (UX storytelling)
    |--------------------------------------------------------------------------
    */   
    private function buildVolumeNarrative(
            array $volume,
            array $query
        ): string {

            $travelType = $query['travel_type'] ?? null;
            $duration   = $query['duration'] ?? null;

            $min = $volume['min'] ?? null;
            $max = $volume['max'] ?? null;

            if (!$min || !$max) {
                return 'We stemmen het volume af op jouw reis en wat je écht nodig hebt.';
            }

            $base = "Met {$min}–{$max} liter zit je precies goed voor deze reis.";

            $travelSentence = match ($travelType) {
                'zomervakantie' => 'Genoeg ruimte voor outfits, maar zonder onnodig gewicht.',
                'wintersport'   => 'Voldoende ruimte voor dikkere kleding en extra lagen.',
                'weekend'       => 'Compact genoeg om licht te reizen, ruim genoeg voor comfort.',
                'zaken'         => 'Efficiënt ingedeeld zodat je georganiseerd blijft.',
                'camping'       => 'Ruimte voor extra spullen zonder dat het onhandelbaar wordt.',
                default         => 'In balans tussen comfort en efficiëntie.',
            };

            return $base . ' ' . $travelSentence;
        }

    
}