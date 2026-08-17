<?php

declare(strict_types=1);

namespace App\StyleGuide\Enum;

enum BagSizePreference: string
{
    case COMPACT = 'compact';
    case MEDIUM = 'middelgroot';
    case LARGE = 'groot';
    case SPACIOUS = 'ruim';

    public function label(): string
    {
        return match ($this) {
            self::COMPACT => 'Compact',
            self::MEDIUM => 'Middelgroot',
            self::LARGE => 'Groot',
            self::SPACIOUS => 'Ruim',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::COMPACT =>
                'Een compacte tas voor je belangrijkste persoonlijke spullen.',

            self::MEDIUM =>
                'Een veelzijdige tas met voldoende ruimte voor dagelijks gebruik.',

            self::LARGE =>
                'Een grotere tas waarin ook een laptop, tablet of documenten passen.',

            self::SPACIOUS =>
                'Een ruime tas voor wie graag veel spullen bij zich draagt.',
        };
    }
}