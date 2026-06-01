<?php

declare(strict_types=1);

namespace App\Guide\Service;

use App\Guide\Entity\Airline;
use App\Guide\Entity\AirlineBaggageRule;
use App\Guide\Entity\AirlineTicketType;
use App\Guide\Repository\AirlineBaggageRuleRepository;

final class TicketBaggageSummaryBuilder
{
    public function __construct(
        private readonly AirlineBaggageRuleRepository $ruleRepository,
    ) {
    }

    public function buildForTicket(
        Airline $airline,
        AirlineTicketType $ticket,
    ): array {
        $summary = $this->emptySummary();

        $rules = $this->ruleRepository->findActiveForAirlineAndTicket($airline, $ticket);

        foreach ($rules as $rule) {
            $scope = $rule->getRuleScope();

            if (!isset($summary[$scope])) {
                continue;
            }

            $summary[$scope] = $this->bagFromRule($rule);
        }

        return $summary;
    }

    public function buildFallbackForAirline(Airline $airline): array
    {
        $summary = $this->emptySummary();

        $rules = $this->ruleRepository->findBy([
            'airline' => $airline,
            'isActive' => true,
        ]);

        foreach ($rules as $rule) {
            $scope = $rule->getRuleScope();

            if (!isset($summary[$scope])) {
                continue;
            }

            /*
             * Fallback voor de airline-pagina:
             * - als er nog niets gevuld is: vul met deze regel
             * - als er al een "niet inbegrepen"-regel staat en deze regel wel inbegrepen is:
             *   vervang door de inbegrepen regel
             */
            if (
                $summary[$scope]['hasRule'] === false
                || (
                    $summary[$scope]['allowed'] === false
                    && $this->isIncluded($rule)
                )
            ) {
                $summary[$scope] = $this->bagFromRule($rule);
            }
        }

        return $summary;
    }

    private function emptySummary(): array
    {
        return [
            'personal' => $this->emptyBag(),
            'cabin' => $this->emptyBag(),
            'hold' => $this->emptyBag(),
        ];
    }

    private function emptyBag(): array
    {
        return [
            'hasRule' => false,
            'allowed' => false,
            'dimensionType' => null,
            'maxHeightCm' => null,
            'maxWidthCm' => null,
            'maxDepthCm' => null,
            'maxLinearCm' => null,
            'maxWeightKg' => null,
            'quantityCabin' => null,
            'quantityHold' => null,
        ];
    }

    private function bagFromRule(AirlineBaggageRule $rule): array
    {
        $quantity = $rule->getQuantityCabin();

        return [
            'hasRule' => true,
            'allowed' => $this->isIncluded($rule),
            'dimensionType' => $rule->getDimensionType(),
            'maxHeightCm' => $rule->getMaxHeightCm(),
            'maxWidthCm' => $rule->getMaxWidthCm(),
            'maxDepthCm' => $rule->getMaxDepthCm(),
            'maxLinearCm' => $rule->getMaxLinearCm(),
            'maxWeightKg' => $rule->getMaxWeightKg(),

            /*
             * Je entity gebruikt nu nog quantityCabin voor alle scopes.
             * Voor Twig geven we beide keys mee, zodat hold veilig quantityHold kan gebruiken.
             */
            'quantityCabin' => $quantity,
            'quantityHold' => $rule->isHold() ? $quantity : null,
        ];
    }

    private function isIncluded(AirlineBaggageRule $rule): bool
    {
        return (int) ($rule->getQuantityCabin() ?? 0) > 0;
    }
}