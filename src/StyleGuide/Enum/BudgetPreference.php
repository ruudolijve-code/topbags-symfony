<?php

declare(strict_types=1);

namespace App\StyleGuide\Enum;

enum BudgetPreference: string
{
    case ACCESSIBLE = 'aantrekkelijke-prijs';
    case QUALITY = 'goede-kwaliteit';
    case PREMIUM = 'premium';
    case LUXURY = 'luxe';
    case NO_PREFERENCE = 'geen-voorkeur';

    public function label(): string
    {
        return match ($this) {
            self::ACCESSIBLE => 'Een mooie tas voor een aantrekkelijke prijs',
            self::QUALITY => 'Goede kwaliteit voor dagelijks gebruik',
            self::PREMIUM => 'Premium materialen en afwerking',
            self::LUXURY => 'Alleen het beste',
            self::NO_PREFERENCE => 'Geen voorkeur',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::ACCESSIBLE => 'Aantrekkelijke prijs',
            self::QUALITY => 'Goede kwaliteit',
            self::PREMIUM => 'Premium',
            self::LUXURY => 'Luxe',
            self::NO_PREFERENCE => 'Geen voorkeur',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ACCESSIBLE =>
                'Je zoekt een modieuze en praktische tas met een scherpe prijs-kwaliteitverhouding.',

            self::QUALITY =>
                'Je wilt een betrouwbare tas voor dagelijks gebruik die mooi blijft en prettig draagt.',

            self::PREMIUM =>
                'Materiaal, afwerking en uitstraling mogen duidelijk van een hoger niveau zijn.',

            self::LUXURY =>
                'Je kiest bewust voor exclusiviteit, vakmanschap en de mooiste materialen.',

            self::NO_PREFERENCE =>
                'De juiste tas is belangrijker dan een vaste prijsklasse.',
        };
    }

    public function minimumPrice(): ?float
    {
        return match ($this) {
            self::ACCESSIBLE => null,
            self::QUALITY => 75.00,
            self::PREMIUM => 150.00,
            self::LUXURY => 250.00,
            self::NO_PREFERENCE => null,
        };
    }

    public function maximumPrice(): ?float
    {
        return match ($this) {
            self::ACCESSIBLE => 75.00,
            self::QUALITY => 150.00,
            self::PREMIUM => 250.00,
            self::LUXURY,
            self::NO_PREFERENCE => null,
        };
    }

    public function shouldFilterProducts(): bool
    {
        return $this !== self::NO_PREFERENCE;
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