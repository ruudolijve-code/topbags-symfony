<?php

namespace App\DataFixtures\Guide;

use App\Entity\Guide\Airline;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class AirlineFixture extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['guide', 'airline'];
    }

    public function load(ObjectManager $manager): void
    {
        $airlines = [
            [
                'slug' => 'ryanair',
                'name' => 'Ryanair',
                'iata' => 'FR',
            ],
            [
                'slug' => 'klm',
                'name' => 'KLM Royal Dutch Airlines',
                'iata' => 'KL',
            ],
            [
                'slug' => 'easyjet',
                'name' => 'easyJet',
                'iata' => 'U2',
            ],
            [
                'slug' => 'transavia',
                'name' => 'Transavia',
                'iata' => 'HV',
            ],
            [
                'slug' => 'lufthansa',
                'name' => 'Lufthansa',
                'iata' => 'LH',
            ],
        ];

        foreach ($airlines as $data) {
            $airline = new Airline();
            $airline
                ->setSlug($data['slug'])
                ->setName($data['name'])
                ->setIataCode($data['iata'])
                ->setIsActive(true);

            $manager->persist($airline);

            // 🔗 Referentie voor ticket-types & baggage rules
            $this->addReference(
                'guide_airline_' . $data['slug'],
                $airline
            );
        }

        $manager->flush();
    }
}