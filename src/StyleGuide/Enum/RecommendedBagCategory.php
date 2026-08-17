<?php

declare(strict_types=1);

namespace App\StyleGuide\Enum;

enum RecommendedBagCategory: string
{
    case BACKPACK = 'backpack';

    case SHOULDER_BAG = 'shoulder_bag';

    case CROSSBODY = 'crossbody';

    case SHOPPER = 'shopper';

    case HANDBAG = 'handbag';
}