<?php

declare(strict_types=1);

namespace App\StyleGuide\Enum;

enum CarryItem: string
{
    case PHONE = 'telefoon';
    case WALLET = 'portemonnee';
    case WATER_BOTTLE = 'waterfles';
    case TABLET = 'tablet';
    case LAPTOP = 'laptop';
    case A4_DOCUMENTS = 'a4-documenten';
    case MANY_ITEMS = 'veel-spullen';

    public function label(): string
    {
        return match ($this) {
            self::PHONE => 'Telefoon',
            self::WALLET => 'Portemonnee',
            self::WATER_BOTTLE => 'Waterfles',
            self::TABLET => 'Tablet',
            self::LAPTOP => 'Laptop',
            self::A4_DOCUMENTS => 'A4-documenten',
            self::MANY_ITEMS => 'Veel spullen',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PHONE =>
                'Voor je telefoon en enkele kleine dagelijkse spullen.',

            self::WALLET =>
                'Voor een portemonnee, sleutels en persoonlijke spullen.',

            self::WATER_BOTTLE =>
                'De tas moet voldoende hoogte en ruimte hebben voor een fles.',

            self::TABLET =>
                'Er moet veilig plaats zijn voor een tablet.',

            self::LAPTOP =>
                'Er moet voldoende ruimte zijn voor een laptop.',

            self::A4_DOCUMENTS =>
                'Documenten en mappen moeten recht in de tas passen.',

            self::MANY_ITEMS =>
                'Je neemt graag wat extra mee en wilt voldoende bewegingsruimte.',
        };
    }

    /**
     * Indicatieve bijdrage aan het benodigde formaat.
     */
    public function sizeScore(): int
    {
        return match ($this) {
            self::PHONE,
            self::WALLET => 1,

            self::WATER_BOTTLE,
            self::TABLET => 2,

            self::LAPTOP,
            self::A4_DOCUMENTS => 3,

            self::MANY_ITEMS => 4,
        };
    }

    /**
     * @return list<self>
     */
    public static function choices(): array
    {
        return self::cases();
    }

    /**
     * @param mixed[] $values
     *
     * @return list<self>
     */
    public static function fromRequestValues(array $values): array
    {
        $items = [];

        foreach ($values as $value) {
            if (!is_string($value)) {
                continue;
            }

            $item = self::tryFrom($value);

            if ($item !== null) {
                $items[$item->value] = $item;
            }
        }

        return array_values($items);
    }
}