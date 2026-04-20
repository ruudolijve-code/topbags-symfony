<?php

namespace App\DataFixtures\Guide;

use App\Entity\Guide\AirlineTicketType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class AirlineTicketTypeFixture extends Fixture implements DependentFixtureInterface
{
    public static function getGroups(): array
    {
        return ['guide', 'airline'];
    }
    public function load(ObjectManager $manager): void
    {
        /**
         * priorityLevel:
         * 1 = meest basic
         * hoger = meer inbegrepen
         */
        $data = [
            'ryanair' => [
                ['slug' => 'basic',     'name' => 'Basic',           'priority' => 1],
                ['slug' => 'regular',   'name' => 'Regular',         'priority' => 2],
                ['slug' => 'priority',  'name' => 'Priority',        'priority' => 3],
            ],

            'easyjet' => [
                ['slug' => 'standard',  'name' => 'Standard',        'priority' => 1],
                ['slug' => 'flexi',     'name' => 'Flexi Fare',      'priority' => 3],
            ],

            'transavia' => [
                ['slug' => 'basic',     'name' => 'Basic',           'priority' => 1],
                ['slug' => 'plus',      'name' => 'Plus',            'priority' => 2],
                ['slug' => 'max',       'name' => 'Max',             'priority' => 3],
            ],

            'klm' => [
                ['slug' => 'light',     'name' => 'Light',           'priority' => 1],
                ['slug' => 'standard',  'name' => 'Standard',        'priority' => 2],
                ['slug' => 'flex',      'name' => 'Flex',            'priority' => 3],
                ['slug' => 'business',  'name' => 'Business',        'priority' => 4],
            ],

            'lufthansa' => [
                ['slug' => 'light',     'name' => 'Light',           'priority' => 1],
                ['slug' => 'classic',   'name' => 'Classic',         'priority' => 2],
                ['slug' => 'flex',      'name' => 'Flex',            'priority' => 3],
                ['slug' => 'business',  'name' => 'Business',        'priority' => 4],
            ],
        ];

        foreach ($data as $airlineSlug => $ticketTypes) {
            $airline = $this->getReference('airline_' . $airlineSlug);

            foreach ($ticketTypes as $row) {
                $ticket = (new AirlineTicketType())
                    ->setAirline($airline)
                    ->setSlug($row['slug'])
                    ->setName($row['name'])
                    ->setPriorityLevel($row['priority'])
                    ->setIsActive(true);

                $manager->persist($ticket);

                // Referentie voor baggage rules
                $this->addReference(
                    'guide_ticket_' . $data['slug'],
                    $ticket
                );
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AirlineFixture::class,
        ];
    }
}