<?php

namespace App\Guide\Service;

use App\Guide\Dto\GuideInput;
use App\Guide\Entity\TravelProfile;
use Doctrine\ORM\EntityManagerInterface;

final class TravelProfileResolver
{
    public function __construct(
        private EntityManagerInterface $em,
        private ArchetypeResolver $archetypeResolver
    ) {}

    public function resolve(GuideInput $in): TravelProfile
    {
        $repo = $this->em->getRepository(TravelProfile::class);

        // =====================================================
        // 1️⃣ Archetype bepalen via dedicated resolver
        // =====================================================
        $code = $this->archetypeResolver->resolve(
            $in->travelTypeSlug,
            $in->transportSlug
        );

        // =====================================================
        // 2️⃣ Exact profiel ophalen
        // =====================================================
        $profile = $repo->findOneBy([
            'code'     => $code,
            'isActive' => true,
        ]);

        if ($profile instanceof TravelProfile) {
            return $profile;
        }

        // =====================================================
        // 3️⃣ Fallback: practical_traveler
        // =====================================================
        $fallback = $repo->findOneBy([
            'code'     => 'practical_traveler',
            'isActive' => true,
        ]);

        if ($fallback instanceof TravelProfile) {
            return $fallback;
        }

        // =====================================================
        // 4️⃣ Fallback: eerste actieve op position
        // =====================================================
        $first = $repo->findOneBy(
            ['isActive' => true],
            ['position' => 'ASC']
        );

        if ($first instanceof TravelProfile) {
            return $first;
        }

        // =====================================================
        // 5️⃣ Hard fail (seed ontbreekt)
        // =====================================================
        throw new \RuntimeException(
            'Geen TravelProfile gevonden. Seed minimaal practical_traveler in travel_profile.'
        );
    }
}