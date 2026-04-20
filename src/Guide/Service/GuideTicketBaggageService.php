<?php

namespace App\Guide\Service;

use App\Entity\Guide\AirlineBaggageRule;

final class GuideTicketBaggageService
{
    /**
     * @return array{
     *   personal: bool,
     *   cabin: bool,
     *   hold: array<int, array{weight:int|null, linear:int|null}>
     * }
     */
    public function summarize(array $rules): array
    {
        $result = [
            'personal' => false,
            'cabin'    => false,
            'hold'     => [],
        ];

        foreach ($rules as $rule) {
            if ($rule->isPersonalItem()) {
                $result['personal'] = true;
            }

            if ($rule->isCabin()) {
                $result['cabin'] = true;
            }

            if ($rule->isHold()) {
                $result['hold'][] = [
                    'weight' => $rule->getMaxWeightKg(),
                    'linear' => $rule->getMaxLinearCm(),
                ];
            }
        }

        return $result;
    }
}