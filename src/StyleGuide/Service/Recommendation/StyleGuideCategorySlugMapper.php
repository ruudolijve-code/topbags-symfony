<?php

declare(strict_types=1);

namespace App\StyleGuide\Service\Recommendation;

use App\StyleGuide\ValueObject\StyleGuideCriteria;

final class StyleGuideCategorySlugMapper
{
    private const AUDIENCE = [
        'dames' => ['damestassen'],
        'heren' => ['herentassen'],
    ];

    private const CARRY_METHOD = [
        'rugzak' => ['rugzakken', 'laptoprugzakken', 'schoolrugzakken', 'vrijetijdsrugzakken', 'daypacks', 'rugtassen'],
        'crossbody' => ['crossbodys', 'telefoontasje'],
        'schoudertas' => ['schoudertassen'],
        'handtas' => ['handtassen'],
        'shopper' => ['shopper', 'shoppers'],
    ];

    /** @return list<string> */
    public function audience(StyleGuideCriteria $criteria): array
    {
        return self::AUDIENCE[$criteria->targetAudience->getCode()] ?? [];
    }

    /** @return list<string> */
    public function carryMethod(StyleGuideCriteria $criteria): array
    {
        return self::CARRY_METHOD[$criteria->carryMethod->getCode()] ?? [];
    }
}
