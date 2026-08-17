<?php

declare(strict_types=1);

namespace App\StyleGuide\Enum;

enum CarryMethod: string
{
    case BACKPACK = 'rugzak';
    case CROSSBODY = 'crossbody';
    case SHOULDER_BAG = 'schoudertas';
    case HANDBAG = 'handtas';
    case SHOPPER = 'shopper';

    public function label(): string
    {
        return match ($this) {
            self::BACKPACK => 'Rugzak',
            self::CROSSBODY => 'Crossbody',
            self::SHOULDER_BAG => 'Schoudertas',
            self::HANDBAG => 'Handtas',
            self::SHOPPER => 'Shopper',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::BACKPACK =>
                'Comfortabel op beide schouders en ideaal wanneer je je handen vrij wilt houden.',

            self::CROSSBODY =>
                'Draagbaar schuin over het lichaam, compact en veilig tijdens dagelijks gebruik.',

            self::SHOULDER_BAG =>
                'Gemakkelijk over één schouder te dragen en snel toegankelijk.',

            self::HANDBAG =>
                'Een verzorgde tas die je aan de hand of over de onderarm draagt.',

            self::SHOPPER =>
                'Een ruime en toegankelijke tas met voldoende plek voor extra spullen.',
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
        if (!is_string($value)) {
            return null;
        }

        return self::tryFrom($value);
    }
}