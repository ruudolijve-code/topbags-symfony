<?php

namespace App\Guide\Dto;

final class AdviceOutput
{
    /**
     * @param array<string, RankedItem[]> $rankedByScope  // personal|cabin|hold => items
     * @param string[] $messages
     */
    public function __construct(
        public readonly GuideInput $input,
        public readonly AdviceResult $advice,
        public readonly array $rankedByScope,
        public readonly array $messages = [],
    ) {}
}