<?php

namespace App\Guide\Service;

use App\Guide\Dto\AdviceOutput;
use App\Guide\Dto\GuideInput;
use App\Catalog\Service\Ranking\HardFilterEngine;
use App\Catalog\Service\Ranking\RankingEngine;

final class GuideEngine
{
    public function __construct(
        private GuideInputValidator $validator,
        private TravelProfileResolver $profileResolver,
        private AirlineRulesResolver $airlineRulesResolver,
        private GuideAdviceBuilder $adviceBuilder,
        private ProductCandidateProvider $candidateProvider,
        private HardFilterEngine $hardFilterEngine,
        private RankingEngine $rankingEngine,
    ) {
    }

    public function run(GuideInput $input): AdviceOutput
    {
        /*
         * 1. Input valideren
         */
        $this->validator->validate($input);

        /*
         * 2. Reisprofiel en airline rules bepalen
         */
        $profile = $this->profileResolver->resolve($input);

        if ($profile === null) {
            throw new \RuntimeException('Geen TravelProfile gevonden voor de opgegeven gidsinput.');
        }

        $rulesByScope = $this->airlineRulesResolver->resolve($input);

        /*
         * 3. Advies opbouwen
         */
        $advice = $this->adviceBuilder->build(
            $input,
            $profile,
            $rulesByScope
        );

        /*
         * 4. Productkandidaten ophalen
         *
         * Let op: context-filtering (alleen shop/reisartikelen)
         * hoort in ProductCandidateProvider of lager in de querylaag te zitten.
         */
        $candidates = $this->candidateProvider->getCandidates($input);

        /*
         * 5. Hard filters toepassen
         */
        $filteredCandidates = $this->hardFilterEngine->filter(
            $candidates,
            $advice,
            $profile,
            $rulesByScope,
            $input
        );

        /*
         * 6. Kandidaten ranken en resultaat teruggeven
         */
        return $this->rankingEngine->rank(
            $filteredCandidates,
            $advice,
            $profile,
            $input
        );
    }
}