<?php

namespace App\Guide\Service;

use App\Guide\Entity\Airline;
use App\Guide\Entity\AirlineTicketType;
use App\Guide\Repository\AirlineBaggageRuleRepository;

class TicketBaggageSummaryBuilder
{
    public function __construct(
        private AirlineBaggageRuleRepository $ruleRepository
    ) {}

    public function buildForTicket(
        Airline $airline,
        AirlineTicketType $ticket
    ): array {
        // vaste structuur (UI verwacht dit)
        $summary = [
            'personal' => $this->emptyBag(),
            'cabin'    => $this->emptyBag(),
            'hold'     => $this->emptyBag(),
        ];

        $rules = $this->ruleRepository
            ->findActiveForAirlineAndTicket($airline, $ticket);

        foreach ($rules as $rule) {
            $scope = $rule->getRuleScope(); // personal | cabin | hold

            if (!isset($summary[$scope])) {
                continue;
            }

            $summary[$scope] = [
                'allowed'       => true,
                'dimensionType' => $rule->getDimensionType(),
                'maxHeightCm'   => $rule->getMaxHeightCm(),
                'maxWidthCm'    => $rule->getMaxWidthCm(),
                'maxDepthCm'    => $rule->getMaxDepthCm(),
                'maxLinearCm'   => $rule->getMaxLinearCm(),
                'maxWeightKg'   => $rule->getMaxWeightKg(),
                'quantityCabin' => $rule->getQuantityCabin(),
            ];
        }

        return $summary;
    }

    private function emptyBag(): array
    {
        return [
            'allowed'       => false,
            'dimensionType' => null,
            'maxHeightCm'   => null,
            'maxWidthCm'    => null,
            'maxDepthCm'    => null,
            'maxLinearCm'   => null,
            'maxWeightKg'   => null,
            'quantityCabin' => null,
        ];
    }



    public function buildFallbackForAirline(Airline $airline): array
    {
        // Start altijd met niets toegestaan
        $summary = [
            'personal' => $this->emptyBag(),
            'cabin'    => $this->emptyBag(),
            'hold'     => $this->emptyBag(),
        ];

        // Alle actieve regels voor airline (ongeacht ticket)
        $rules = $this->ruleRepository->findBy([
            'airline'  => $airline,
            'isActive' => true,
        ]);

        foreach ($rules as $rule) {
            $scope = $rule->getRuleScope(); // personal | cabin | hold

            // Alleen vullen als die scope nog niet gevuld is
            if ($summary[$scope]['allowed'] === false) {
                $summary[$scope] = [
                    'allowed'       => true,
                    'dimensionType' => $rule->getDimensionType(),
                    'maxHeightCm'   => $rule->getMaxHeightCm(),
                    'maxWidthCm'    => $rule->getMaxWidthCm(),
                    'maxDepthCm'    => $rule->getMaxDepthCm(),
                    'maxLinearCm'   => $rule->getMaxLinearCm(),
                    'maxWeightKg'   => $rule->getMaxWeightKg(),
                ];
            }
        }

        return $summary;
    }
}