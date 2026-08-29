<?php

declare(strict_types=1);

namespace App\StyleGuide\ValueObject;

final readonly class StyleAffinityResult
{
    /** @param list<string> $reasons */
    public function __construct(public int $affinityScore, public int $overrideScore, public array $reasons) {}
}
