<?php

declare(strict_types=1);

namespace App\StyleGuide\Enum;

enum UseMoment: string
{
    case DAILY = 'dagelijks';
    case WORK = 'werk';
    case SHOPPING = 'winkelen';
    case OCCASION = 'gelegenheid';
    case TRAVEL = 'reizen';
    case EVENING = 'avond';

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Iedere dag',
            self::WORK => 'Werk & zakelijk',
            self::SHOPPING => 'Winkelen & vrije tijd',
            self::OCCASION => 'Feestelijke gelegenheid',
            self::TRAVEL => 'Dagje weg & reizen',
            self::EVENING => 'Avondje uit',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DAILY =>
                'Een veelzijdige tas voor je dagelijkse bezigheden.',

            self::WORK =>
                'Een verzorgde en praktische tas voor werk of afspraken.',

            self::SHOPPING =>
                'Een comfortabele tas voor stad, boodschappen en vrije tijd.',

            self::OCCASION =>
                'Een stijlvolle tas voor een feest, bruiloft of bijzondere dag.',

            self::TRAVEL =>
                'Een praktische tas voor onderweg, uitstapjes en korte reizen.',

            self::EVENING =>
                'Een compacte tas voor diner, theater of een avond uit.',
        };
    }

    /**
     * @return list<self>
     */
    public static function choices(): array
    {
        return self::cases();
    }

    public static function tryFromRequest(mixed $value): ?self
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return self::tryFrom($value);
    }
}