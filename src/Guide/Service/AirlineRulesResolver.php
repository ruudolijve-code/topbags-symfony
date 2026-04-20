<?php

namespace App\Guide\Service;

use App\Guide\Dto\GuideInput;
use App\Guide\Entity\Airline;
use App\Guide\Entity\AirlineTicketType;
use App\Guide\Entity\AirlineBaggageRule;
use Doctrine\ORM\EntityManagerInterface;

final class AirlineRulesResolver
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * @return array{personal: AirlineBaggageRule[], cabin: AirlineBaggageRule[], hold: AirlineBaggageRule[]}
     */
    public function resolve(GuideInput $in): array
    {
        $out = ['personal' => [], 'cabin' => [], 'hold' => []];

        if ($in->transportSlug !== 'plane' || !$in->airlineSlug || !$in->ticketSlug) {
            return $out; // geen airline filtering
        }

        $airline = $this->em->getRepository(Airline::class)->findOneBy([
            'slug' => $in->airlineSlug,
            'isActive' => true,
        ]);

        if (!$airline) {
            return $out;
        }

        $ticket = $this->em->getRepository(AirlineTicketType::class)->findOneBy([
            'airline' => $airline,
            'slug' => $in->ticketSlug,
            'isActive' => true,
        ]);

        if (!$ticket) {
            return $out;
        }

        $rules = $this->em->getRepository(AirlineBaggageRule::class)->findBy([
            'airline' => $airline,
            'ticketType' => $ticket,
            'isActive' => true,
        ]);

        foreach ($rules as $r) {
            $scope = $r->getRuleScope(); // personal|cabin|hold
            if (isset($out[$scope])) {
                $out[$scope][] = $r;
            }
        }

        return $out;
    }
}