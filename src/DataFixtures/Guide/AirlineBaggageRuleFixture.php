<?php

namespace App\DataFixtures\Guide;

use App\Entity\Guide\AirlineBaggageRule;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class AirlineBaggageRuleFixture extends Fixture implements DependentFixtureInterface
{
    public static function getGroups(): array
    {
        return ['guide', 'airline'];
    }


    public function load(ObjectManager $manager): void
    {
        foreach ($this->getRules() as $row) {
            $rule = new AirlineBaggageRule();

            // ✈️ Airline (bron van waarheid)
            $rule->setAirline(
                $this->getReference('guide_airline_' . $row['airline'])
            );

            // 🎫 Ticket type (optioneel)
            if (!empty($row['ticket'])) {
                $rule->setTicketType(
                    $this->getReference(
                        'guide_ticket_' . $row['airline'] . '_' . $row['ticket']
                    )
                );
            }

            $rule
                ->setRuleScope($row['scope'])                // personal | cabin | hold
                ->setDimensionType($row['dimension'])        // box | linear_sum
                ->setMaxHeightCm($row['h'] ?? null)
                ->setMaxWidthCm($row['w'] ?? null)
                ->setMaxDepthCm($row['d'] ?? null)
                ->setMaxLinearCm($row['linear'] ?? null)
                ->setMaxWeightKg($row['weight'] ?? null)
                ->setIsActive(true);

            $manager->persist($rule);
        }

        $manager->flush();
    }

    /**
     * Definitie van airline baggage rules
     */
    private function getRules(): array
    {
        return [

            /* ===========================
             * RYANAIR
             * =========================== */
            [
                'airline'   => 'ryanair',
                'ticket'    => 'basic',
                'scope'     => 'personal',
                'dimension' => 'box',
                'h' => 40, 'w' => 25, 'd' => 20,
            ],
            [
                'airline'   => 'ryanair',
                'ticket'    => 'priority',
                'scope'     => 'cabin',
                'dimension' => 'box',
                'h' => 55, 'w' => 40, 'd' => 20,
                'weight' => 10,
            ],
            [
                'airline'   => 'ryanair',
                'ticket'    => 'regular',
                'scope'     => 'hold',
                'dimension' => 'linear_sum',
                'linear'    => 158,
                'weight'    => 20,
            ],

            /* ===========================
             * TRANSAVIA
             * =========================== */
            [
                'airline'   => 'transavia',
                'ticket'    => 'basic',
                'scope'     => 'personal',
                'dimension' => 'box',
                'h' => 40, 'w' => 30, 'd' => 20,
            ],
            [
                'airline'   => 'transavia',
                'ticket'    => 'plus',
                'scope'     => 'cabin',
                'dimension' => 'box',
                'h' => 55, 'w' => 40, 'd' => 25,
                'weight' => 10,
            ],
            [
                'airline'   => 'transavia',
                'ticket'    => 'max',
                'scope'     => 'hold',
                'dimension' => 'linear_sum',
                'linear'    => 158,
                'weight'    => 23,
            ],

            /* ===========================
             * KLM
             * =========================== */
            [
                'airline'   => 'klm',
                'ticket'    => 'light',
                'scope'     => 'cabin',
                'dimension' => 'box',
                'h' => 55, 'w' => 35, 'd' => 25,
                'weight' => 12,
            ],
            [
                'airline'   => 'klm',
                'ticket'    => 'business',
                'scope'     => 'cabin',
                'dimension' => 'box',
                'h' => 55, 'w' => 35, 'd' => 25,
                'weight' => 18,
            ],
            [
                'airline'   => 'klm',
                'ticket'    => 'light',
                'scope'     => 'hold',
                'dimension' => 'linear_sum',
                'linear'    => 158,
                'weight'    => 23,
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            AirlineFixture::class,
            AirlineTicketTypeFixture::class,
        ];
    }
}