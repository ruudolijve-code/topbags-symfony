<?php

declare(strict_types=1);

namespace App\StyleGuide\Enum;

enum OutfitPreference: string
{
    case TIMELESS_BASICS = 'tijdloze-basics';
    case LUXURY_REFINED = 'luxe-verfijnd';
    case COLORFUL_CREATIVE = 'kleurrijk-creatief';
    case NATURAL_RELAXED = 'naturel-ontspannen';
    case TRENDY_CHANGING = 'trendy-wisselend';

    public function label(): string
    {
        return match ($this) {
            self::TIMELESS_BASICS => 'Tijdloze basics',
            self::LUXURY_REFINED => 'Luxe & verfijnd',
            self::COLORFUL_CREATIVE => 'Kleurrijk & creatief',
            self::NATURAL_RELAXED => 'Naturel & ontspannen',
            self::TRENDY_CHANGING => 'Trendy & wisselend',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TIMELESS_BASICS =>
                'Jeans, een witte blouse, blazer, sneakers en accessoires die overal bij passen.',

            self::LUXURY_REFINED =>
                'Mooie stoffen, rustige kleuren, elegante schoenen en zorgvuldig gekozen accessoires.',

            self::COLORFUL_CREATIVE =>
                'Prints, kleur, sieraden en bijzondere accessoires die je persoonlijkheid laten zien.',

            self::NATURAL_RELAXED =>
                'Linnen, wol, katoen, aardetinten en comfortabele, ontspannen vormen.',

            self::TRENDY_CHANGING =>
                'Nieuwe silhouetten, seizoenskleuren en accessoires waarmee je jouw outfit regelmatig vernieuwt.',
        };
    }

    /**
     * Voor de latere stijlscore.
     *
     * @return array<string, int>
     */
    public function styleScores(): array
    {
        return match ($this) {
            self::TIMELESS_BASICS => [
                StyleWorld::CASUAL_CHIC->value => 3,
                StyleWorld::CLASSIC_POLISHED->value => 1,
            ],

            self::LUXURY_REFINED => [
                StyleWorld::LUXURY_ELEGANT->value => 3,
                StyleWorld::CLASSIC_POLISHED->value => 2,
            ],

            self::COLORFUL_CREATIVE => [
                StyleWorld::BOHEMIAN_COLORFUL->value => 3,
                StyleWorld::FASHION_FORWARD->value => 1,
            ],

            self::NATURAL_RELAXED => [
                StyleWorld::NATURAL_TIMELESS->value => 3,
                StyleWorld::CASUAL_CHIC->value => 1,
            ],

            self::TRENDY_CHANGING => [
                StyleWorld::FASHION_FORWARD->value => 3,
                StyleWorld::LUXURY_ELEGANT->value => 1,
            ],
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