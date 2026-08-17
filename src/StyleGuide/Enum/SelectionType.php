<?php

declare(strict_types=1);

namespace App\StyleGuide\Enum;

enum SelectionType: string
{
    case SINGLE = 'single';
    case MULTIPLE = 'multiple';

    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Eén antwoord',
            self::MULTIPLE => 'Meerdere antwoorden',
        };
    }
}