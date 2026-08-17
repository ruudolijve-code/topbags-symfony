<?php

declare(strict_types=1);

namespace App\StyleGuide\Enum;

enum StyleWorld: string
{
    case CASUAL_CHIC = 'casual-chic';
    case LUXURY_ELEGANT = 'luxe-elegant';
    case BOHEMIAN_COLORFUL = 'bohemian-kleurrijk';
    case NATURAL_TIMELESS = 'naturel-tijdloos';
    case CLASSIC_POLISHED = 'klassiek-verzorgd';
    case FASHION_FORWARD = 'fashion-forward';

    public function label(): string
    {
        return match ($this) {
            self::CASUAL_CHIC => 'Casual Chic',
            self::LUXURY_ELEGANT => 'Luxe & Elegant',
            self::BOHEMIAN_COLORFUL => 'Bohemian & Kleurrijk',
            self::NATURAL_TIMELESS => 'Naturel & Tijdloos',
            self::CLASSIC_POLISHED => 'Klassiek & Verzorgd',
            self::FASHION_FORWARD => 'Fashion Forward',
        };
    }

    public function emotion(): string
    {
        return match ($this) {
            self::CASUAL_CHIC => 'Comfort',
            self::LUXURY_ELEGANT => 'Zelfvertrouwen',
            self::BOHEMIAN_COLORFUL => 'Vrijheid',
            self::NATURAL_TIMELESS => 'Rust',
            self::CLASSIC_POLISHED => 'Zekerheid',
            self::FASHION_FORWARD => 'Inspiratie',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CASUAL_CHIC =>
                'Verzorgd, comfortabel en veelzijdig. Je zoekt een tas die moeiteloos bij je dagelijkse leven past.',

            self::LUXURY_ELEGANT =>
                'Verfijnd, stijlvol en bewust gekozen. Materiaal, afwerking en uitstraling mogen bijzonder zijn.',

            self::BOHEMIAN_COLORFUL =>
                'Creatief, speels en persoonlijk. Je tas mag kleur hebben en laten zien wie je bent.',

            self::NATURAL_TIMELESS =>
                'Authentiek, rustig en duurzaam. Je kiest graag voor natuurlijke materialen en tijdloze kleuren.',

            self::CLASSIC_POLISHED =>
                'Betrouwbaar, overzichtelijk en verzorgd. Een tas moet praktisch zijn en jarenlang meegaan.',

            self::FASHION_FORWARD =>
                'Eigentijds, modieus en vernieuwend. Je tas maakt je outfit compleet en mag de trends volgen.',
        };
    }

    public function motto(): string
    {
        return match ($this) {
            self::CASUAL_CHIC =>
                'Mijn tas moet overal bij passen.',

            self::LUXURY_ELEGANT =>
                'Kwaliteit zie je zonder dat het schreeuwt.',

            self::BOHEMIAN_COLORFUL =>
                'Mijn tas mag net zo vrolijk zijn als ik.',

            self::NATURAL_TIMELESS =>
                'Ik koop liever één goede tas dan drie nieuwe.',

            self::CLASSIC_POLISHED =>
                'Een goede tas moet je iedere dag kunnen vertrouwen.',

            self::FASHION_FORWARD =>
                'Mijn stijl verandert met het seizoen.',
        };
    }

    /**
     * @return list<string>
     */
    public function characteristics(): array
    {
        return match ($this) {
            self::CASUAL_CHIC => [
                'Comfortabel',
                'Veelzijdig',
                'Verzorgd',
            ],
            self::LUXURY_ELEGANT => [
                'Verfijnd',
                'Hoogwaardig',
                'Zelfverzekerd',
            ],
            self::BOHEMIAN_COLORFUL => [
                'Kleurrijk',
                'Creatief',
                'Speels',
            ],
            self::NATURAL_TIMELESS => [
                'Authentiek',
                'Duurzaam',
                'Tijdloos',
            ],
            self::CLASSIC_POLISHED => [
                'Betrouwbaar',
                'Functioneel',
                'Overzichtelijk',
            ],
            self::FASHION_FORWARD => [
                'Modieus',
                'Eigentijds',
                'Vernieuwend',
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