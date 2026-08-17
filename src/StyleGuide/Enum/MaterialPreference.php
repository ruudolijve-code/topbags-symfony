<?php

declare(strict_types=1);

namespace App\StyleGuide\Enum;

enum MaterialPreference: string
{
    case LEATHER = 'leer';
    case VEGAN = 'vegan';
    case CANVAS = 'canvas';
    case NYLON = 'nylon';
    case NO_PREFERENCE = 'geen-voorkeur';

    public function label(): string
    {
        return match ($this) {
            self::LEATHER => 'Leer',
            self::VEGAN => 'Vegan',
            self::CANVAS => 'Canvas',
            self::NYLON => 'Nylon',
            self::NO_PREFERENCE => 'Geen voorkeur',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::LEATHER =>
                'Een natuurlijke en duurzame uitstraling die met de tijd vaak nog mooier wordt.',

            self::VEGAN =>
                'Een diervrij alternatief met de uitstraling van leer en een eigentijds karakter.',

            self::CANVAS =>
                'Stevig textiel met een ontspannen, natuurlijke en vaak sportieve uitstraling.',

            self::NYLON =>
                'Licht, praktisch en onderhoudsvriendelijk voor intensief dagelijks gebruik.',

            self::NO_PREFERENCE =>
                'Het materiaal is voor jou minder belangrijk dan uitstraling, formaat en gebruiksgemak.',
        };
    }

    /**
     * Geeft aan of deze keuze later als actief productfilter moet worden gebruikt.
     */
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
        if (!is_string($value)) {
            return null;
        }

        return self::tryFrom($value);
    }
}