<?php

declare(strict_types=1);

namespace App\StyleGuide\Controller;

use App\StyleGuide\Engine\StyleGuideEngine;
use App\Catalog\Entity\Product;
use App\StyleGuide\Enum\CarryItem;
use App\StyleGuide\Repository\StyleGuideWorldRepository;
use App\StyleGuide\Service\Recommendation\BagFitCalculator;
use App\StyleGuide\Service\Recommendation\BagRecommendationProfileCalculator;
use App\StyleGuide\Service\Recommendation\BagSizeCalculator;
use App\StyleGuide\Service\Recommendation\StyleGuideCriteriaFactory;
use App\StyleGuide\Service\StyleGuideProductMatcher;
use App\StyleGuide\Service\StyleGuideSession;
use App\StyleGuide\Service\Ai\OpenAiStyleGuideAdvisor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

    #[Route('/stijlgids', name: 'style_guide_')]
    final class StyleGuideController extends AbstractController
    {
        /**
         * De Stijlgids hoort altijd bij de tassencontext.
         *
         * Producten mogen via product_context zowel in shop als bags
         * voorkomen, maar de gebruikerservaring van de Stijlgids zelf
         * blijft altijd binnen CONTEXT_BAGS.
         */
        private const CONTEXT = Product::CONTEXT_BAGS;

        #[Route('', name: 'index', methods: ['GET'])]
        public function index(
            StyleGuideEngine $engine,
        ): Response {
            return $this->renderStyleGuide('style_guide/index.html.twig', [
                'hasUseMoment' => $engine->hasAnswer('use_moment'),
                'hasStyleWorld' => $engine->hasAnswer('style_world'),
            ]);
        }

    #[Route('/gebruik', name: 'use', methods: ['GET', 'POST'])]
    public function use(
        Request $request,
        StyleGuideEngine $engine,
    ): Response {
        $question = $engine->getQuestion('use_moment');
        $selectedAnswer = $engine->getAnswer($question->getCode());
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'style_guide_use',
                (string) $request->request->get('_token'),
            )) {
                throw $this->createAccessDeniedException(
                    'De beveiligingscontrole is mislukt.',
                );
            }

            $answer = $engine->normalizeSubmittedAnswer(
                $question,
                $request->request->get('use_moment'),
            );

            if (!is_string($answer)) {
                $error = 'Kies waarvoor je de tas vooral wilt gebruiken.';
            } else {
                $engine->saveAnswer($question, $answer);

                return $this->redirectToRoute('style_guide_audience');
            }
        }

        return $this->renderStyleGuide('style_guide/use.html.twig', [
            'question' => $question,
            'answers' => $question->getAnswers(),
            'selectedAnswer' => $selectedAnswer,
            'error' => $error,
        ]);
    }

    #[Route('/doelgroep', name: 'audience', methods: ['GET', 'POST'])]
    public function audience(Request $request, StyleGuideEngine $engine): Response
    {
        if (!$engine->hasAnswer('use_moment')) {
            return $this->redirectToRoute('style_guide_use');
        }

        $question = $engine->getQuestion('target_audience');
        $selectedAnswer = $engine->getAnswer($question->getCode());
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('style_guide_audience', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('De beveiligingscontrole is mislukt.');
            }
            $answer = $engine->normalizeSubmittedAnswer($question, $request->request->get('target_audience'));
            if (!is_string($answer)) {
                $error = 'Kies voor wie je een tas zoekt.';
            } else {
                $engine->saveAnswer($question, $answer);
                return $this->redirectToRoute('style_guide_style');
            }
        }

        return $this->renderStyleGuide('style_guide/audience.html.twig', [
            'question' => $question, 'answers' => $question->getAnswers(),
            'selectedAnswer' => $selectedAnswer, 'error' => $error,
        ]);
    }

    #[Route('/stijl', name: 'style', methods: ['GET', 'POST'])]
    public function style(
        Request $request,
        StyleGuideEngine $engine,
    ): Response {
        if (!$engine->hasAnswer('use_moment')) {
            return $this->redirectToRoute('style_guide_use');
        }
        if (!$engine->hasAnswer('target_audience')) {
            return $this->redirectToRoute('style_guide_audience');
        }

        $question = $engine->getQuestion('style_world');
        $selectedAnswer = $engine->getAnswer($question->getCode());
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'style_guide_style',
                (string) $request->request->get('_token'),
            )) {
                throw $this->createAccessDeniedException(
                    'De beveiligingscontrole is mislukt.',
                );
            }

            $answer = $engine->normalizeSubmittedAnswer(
                $question,
                $request->request->get('style_world'),
            );

            if (!is_string($answer)) {
                $error = 'Kies de stijlwereld die het beste bij je past.';
            } else {
                $engine->saveAnswer($question, $answer);

                return $this->redirectToRoute('style_guide_outfit');
            }
        }

        return $this->renderStyleGuide('style_guide/style.html.twig', [
            'question' => $question,
            'answers' => $question->getAnswers(),
            'selectedAnswer' => $selectedAnswer,
            'error' => $error,
        ]);
    }

    #[Route('/outfit', name: 'outfit', methods: ['GET', 'POST'])]
    public function outfit(
        Request $request,
        StyleGuideEngine $engine,
    ): Response {
        if (!$engine->hasAnswer('use_moment')) {
            return $this->redirectToRoute('style_guide_use');
        }

        if (!$engine->hasAnswer('style_world')) {
            return $this->redirectToRoute('style_guide_style');
        }

        $question = $engine->getQuestion('outfit');
        $selectedAnswer = $engine->getAnswer($question->getCode());
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'style_guide_outfit',
                (string) $request->request->get('_token'),
            )) {
                throw $this->createAccessDeniedException(
                    'De beveiligingscontrole is mislukt.',
                );
            }

            $answer = $engine->normalizeSubmittedAnswer(
                $question,
                $request->request->get('outfit_preference'),
            );

            if (!is_string($answer)) {
                $error = 'Kies welke outfits het beste bij jou passen.';
            } else {
                $engine->saveAnswer($question, $answer);

                return $this->redirectToRoute('style_guide_content');
            }
        }

        return $this->renderStyleGuide('style_guide/outfit.html.twig', [
            'question' => $question,
            'answers' => $question->getAnswers(),
            'selectedAnswer' => $selectedAnswer,
            'error' => $error,
        ]);
    }

    #[Route('/inhoud', name: 'content', methods: ['GET', 'POST'])]
    public function content(
        Request $request,
        StyleGuideEngine $engine,
        StyleGuideSession $styleGuideSession,
        BagSizeCalculator $bagSizeCalculator,
    ): Response {
        if (!$engine->hasAnswer('use_moment')) {
            return $this->redirectToRoute('style_guide_use');
        }

        if (!$engine->hasAnswer('style_world')) {
            return $this->redirectToRoute('style_guide_style');
        }

        if (!$engine->hasAnswer('outfit')) {
            return $this->redirectToRoute('style_guide_outfit');
        }

        $question = $engine->getQuestion('carry_items');
        $selectedAnswers = $engine->getAnswer($question->getCode());
        $error = null;

        if (!is_array($selectedAnswers)) {
            $selectedAnswers = [];
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'style_guide_content',
                (string) $request->request->get('_token'),
            )) {
                throw $this->createAccessDeniedException(
                    'De beveiligingscontrole is mislukt.',
                );
            }

            $answer = $engine->normalizeSubmittedAnswer(
                $question,
                $request->request->all('carry_items'),
            );

            if (!is_array($answer) || $answer === []) {
                $error = 'Kies minimaal één ding dat je meestal meeneemt.';
            } else {
                $engine->saveAnswer($question, $answer);

                /*
                * Tijdelijke backwards compatibility.
                * Dit blok verdwijnt zodra BagFitCalculator leidend is.
                */
                $carryItems = CarryItem::fromRequestValues($answer);

                $styleGuideSession->setCarryItems($carryItems);

                $styleGuideSession->setBagSizePreference(
                    $bagSizeCalculator->calculate($carryItems),
                );

                if (in_array('laptop', $answer, true)) {
                    return $this->redirectToRoute('style_guide_laptop');
                }

                /*
                * Oude laptopmaat opruimen wanneer laptop later
                * uit de selectie wordt verwijderd.
                */
                $engine->removeAnswer('laptop_size');

                return $this->redirectToRoute(
                    'style_guide_carry_method',
                );
            }
        }

        return $this->renderStyleGuide('style_guide/content.html.twig', [
            'question' => $question,
            'answers' => $question->getAnswers(),
            'selectedAnswers' => $selectedAnswers,
            'error' => $error,
        ]);
    }

    #[Route('/laptop', name: 'laptop', methods: ['GET', 'POST'])]
    public function laptop(
        Request $request,
        StyleGuideEngine $engine,
    ): Response {
        if (!$engine->hasAnswer('use_moment')) {
            return $this->redirectToRoute('style_guide_use');
        }

        if (!$engine->hasAnswer('style_world')) {
            return $this->redirectToRoute('style_guide_style');
        }

        if (!$engine->hasAnswer('outfit')) {
            return $this->redirectToRoute('style_guide_outfit');
        }

        if (!$engine->hasAnswer('carry_items')) {
            return $this->redirectToRoute('style_guide_content');
        }

        $carryItems = $engine->getAnswer('carry_items');

        if (
            !is_array($carryItems)
            || !in_array('laptop', $carryItems, true)
        ) {
            $engine->removeAnswer('laptop_size');

            return $this->redirectToRoute(
                'style_guide_carry_method',
            );
        }

        $question = $engine->getQuestion('laptop_size');
        $selectedAnswer = $engine->getAnswer($question->getCode());
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'style_guide_laptop',
                (string) $request->request->get('_token'),
            )) {
                throw $this->createAccessDeniedException(
                    'De beveiligingscontrole is mislukt.',
                );
            }

            $answer = $engine->normalizeSubmittedAnswer(
                $question,
                $request->request->get('laptop_size'),
            );

            if (!is_string($answer)) {
                $error = 'Kies de maat van jouw laptop.';
            } else {
                $engine->saveAnswer($question, $answer);

                return $this->redirectToRoute(
                    'style_guide_carry_method',
                );
            }
        }

        return $this->renderStyleGuide('style_guide/laptop.html.twig', [
            'question' => $question,
            'answers' => $question->getAnswers(),
            'selectedAnswer' => $selectedAnswer,
            'error' => $error,
        ]);
    }

    #[Route('/draagwijze', name: 'carry_method', methods: ['GET', 'POST'])]
    public function carryMethod(
        Request $request,
        StyleGuideEngine $engine,
    ): Response {
        if (!$engine->hasAnswer('use_moment')) {
            return $this->redirectToRoute('style_guide_use');
        }

        if (!$engine->hasAnswer('style_world')) {
            return $this->redirectToRoute('style_guide_style');
        }

        if (!$engine->hasAnswer('outfit')) {
            return $this->redirectToRoute('style_guide_outfit');
        }

        if (!$engine->hasAnswer('carry_items')) {
            return $this->redirectToRoute('style_guide_content');
        }

        $carryItems = $engine->getAnswer('carry_items');

        if (
            is_array($carryItems)
            && in_array('laptop', $carryItems, true)
            && !$engine->hasAnswer('laptop_size')
        ) {
            return $this->redirectToRoute('style_guide_laptop');
        }

        $question = $engine->getQuestion('carry_method');
        $selectedAnswer = $engine->getAnswer($question->getCode());
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'style_guide_carry_method',
                (string) $request->request->get('_token'),
            )) {
                throw $this->createAccessDeniedException(
                    'De beveiligingscontrole is mislukt.',
                );
            }

            $answer = $engine->normalizeSubmittedAnswer(
                $question,
                $request->request->get('carry_method'),
            );

            if (!is_string($answer)) {
                $error = 'Kies hoe je jouw tas het liefst draagt.';
            } else {
                $engine->saveAnswer($question, $answer);

                return $this->redirectToRoute('style_guide_material');
            }
        }

        return $this->renderStyleGuide('style_guide/carry_method.html.twig', [
            'question' => $question,
            'answers' => $question->getAnswers(),
            'selectedAnswer' => $selectedAnswer,
            'error' => $error,
        ]);
    }

    #[Route('/materiaal', name: 'material', methods: ['GET', 'POST'])]
    public function material(
        Request $request,
        StyleGuideEngine $engine,
    ): Response {
        if (!$engine->hasAnswer('use_moment')) {
            return $this->redirectToRoute('style_guide_use');
        }

        if (!$engine->hasAnswer('style_world')) {
            return $this->redirectToRoute('style_guide_style');
        }

        if (!$engine->hasAnswer('outfit')) {
            return $this->redirectToRoute('style_guide_outfit');
        }

        if (!$engine->hasAnswer('carry_items')) {
            return $this->redirectToRoute('style_guide_content');
        }

        if (!$engine->hasAnswer('carry_method')) {
            return $this->redirectToRoute('style_guide_carry_method');
        }

        $question = $engine->getQuestion('material');
        $selectedAnswer = $engine->getAnswer($question->getCode());
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'style_guide_material',
                (string) $request->request->get('_token'),
            )) {
                throw $this->createAccessDeniedException(
                    'De beveiligingscontrole is mislukt.',
                );
            }

            $answer = $engine->normalizeSubmittedAnswer(
                $question,
                $request->request->get('material_preference'),
            );

            if (!is_string($answer)) {
                $error = 'Kies welk materiaal jouw voorkeur heeft.';
            } else {
                $engine->saveAnswer($question, $answer);

                return $this->redirectToRoute('style_guide_budget');
            }
        }

        return $this->renderStyleGuide('style_guide/material.html.twig', [
            'question' => $question,
            'answers' => $question->getAnswers(),
            'selectedAnswer' => $selectedAnswer,
            'error' => $error,
        ]);
    }

    #[Route('/budget', name: 'budget', methods: ['GET', 'POST'])]
    public function budget(
        Request $request,
        StyleGuideEngine $engine,
    ): Response {
        if (!$engine->hasAnswer('use_moment')) {
            return $this->redirectToRoute('style_guide_use');
        }

        if (!$engine->hasAnswer('style_world')) {
            return $this->redirectToRoute('style_guide_style');
        }

        if (!$engine->hasAnswer('outfit')) {
            return $this->redirectToRoute('style_guide_outfit');
        }

        if (!$engine->hasAnswer('carry_items')) {
            return $this->redirectToRoute('style_guide_content');
        }

        if (!$engine->hasAnswer('carry_method')) {
            return $this->redirectToRoute('style_guide_carry_method');
        }

        if (!$engine->hasAnswer('material')) {
            return $this->redirectToRoute('style_guide_material');
        }

        $question = $engine->getQuestion('budget');
        $selectedAnswer = $engine->getAnswer($question->getCode());
        $error = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'style_guide_budget',
                (string) $request->request->get('_token'),
            )) {
                throw $this->createAccessDeniedException(
                    'De beveiligingscontrole is mislukt.',
                );
            }

            $answer = $engine->normalizeSubmittedAnswer(
                $question,
                $request->request->get('budget_preference'),
            );

            if (!is_string($answer)) {
                $error = 'Kies welk kwaliteitsniveau het beste bij je past.';
            } else {
                $engine->saveAnswer($question, $answer);

                return $this->redirectToRoute('style_guide_wishes');
            }
        }

        return $this->renderStyleGuide('style_guide/budget.html.twig', [
            'question' => $question,
            'answers' => $question->getAnswers(),
            'selectedAnswer' => $selectedAnswer,
            'error' => $error,
        ]);
    }

    #[Route('/jouw-wensen', name: 'wishes', methods: ['GET', 'POST'])]
    public function wishes(Request $request, StyleGuideEngine $engine, StyleGuideSession $styleGuideSession): Response
    {
        if (!$engine->hasAnswer('budget')) {
            return $this->redirectToRoute('style_guide_budget');
        }

        $error = null;
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('style_guide_wishes', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('De beveiligingscontrole is mislukt.');
            }

            $wish = $request->request->has('skip')
                ? ''
                : trim((string) $request->request->get('personal_wish', ''));
            if (mb_strlen($wish) > 1000) {
                $error = 'Houd je toelichting korter dan 1000 tekens.';
            } else {
                $styleGuideSession->setPersonalWish($wish);

                return $this->redirectToRoute('style_guide_result');
            }
        }

        return $this->renderStyleGuide('style_guide/wishes.html.twig', [
            'personalWish' => $styleGuideSession->getPersonalWish(),
            'error' => $error,
        ]);
    }

    #[Route('/resultaat', name: 'result', methods: ['GET'])]
    public function result(
        StyleGuideEngine $engine,
        StyleGuideSession $styleGuideSession,
        StyleGuideWorldRepository $styleGuideWorldRepository,
        BagFitCalculator $bagFitCalculator,
        BagRecommendationProfileCalculator $recommendationProfileCalculator,
        StyleGuideCriteriaFactory $criteriaFactory,
        StyleGuideProductMatcher $productMatcher,
        OpenAiStyleGuideAdvisor $aiAdvisor,
    ): Response {
        $requiredQuestions = [
            'use_moment' => 'style_guide_use',
            'target_audience' => 'style_guide_audience',
            'style_world' => 'style_guide_style',
            'outfit' => 'style_guide_outfit',
            'carry_items' => 'style_guide_content',
            'carry_method' => 'style_guide_carry_method',
            'material' => 'style_guide_material',
            'budget' => 'style_guide_budget',
        ];

        foreach ($requiredQuestions as $questionCode => $routeName) {
            if (!$engine->hasAnswer($questionCode)) {
                return $this->redirectToRoute($routeName);
            }
        }

        /*
        * Laptop is een conditionele vervolgvraag.
        */
        $carryItemCodes = $engine->getAnswer('carry_items');

        $hasLaptop = is_array($carryItemCodes)
            && in_array('laptop', $carryItemCodes, true);

        if ($hasLaptop && !$engine->hasAnswer('laptop_size')) {
            return $this->redirectToRoute('style_guide_laptop');
        }

        /*
        * Geselecteerde database-antwoorden ophalen.
        */
        $useMoment =
            $engine->getSelectedAnswerEntity('use_moment');

        $targetAudience =
            $engine->getSelectedAnswerEntity('target_audience');

        $styleWorldAnswer =
            $engine->getSelectedAnswerEntity('style_world');

        $styleWorld = $styleWorldAnswer !== null
            ? $styleGuideWorldRepository->findActiveBySlug(
                $styleWorldAnswer->getCode(),
            )
            : null;

        $outfitPreference =
            $engine->getSelectedAnswerEntity('outfit');

        $carryItems =
            $engine->getSelectedAnswerEntities('carry_items');

        $laptopSize = $hasLaptop
            ? $engine->getSelectedAnswerEntity('laptop_size')
            : null;

        $carryMethod =
            $engine->getSelectedAnswerEntity('carry_method');

        $materialPreference =
            $engine->getSelectedAnswerEntity('material');

        $budgetPreference =
            $engine->getSelectedAnswerEntity('budget');

        /*
        * Alle benodigde onderdelen moeten geldig zijn voordat
        * we het profiel en de matcher opbouwen.
        */
        if (
            $useMoment === null
            || $targetAudience === null
            || $styleWorldAnswer === null
            || $styleWorld === null
            || $outfitPreference === null
            || $carryItems === []
            || ($hasLaptop && $laptopSize === null)
            || $carryMethod === null
            || $materialPreference === null
            || $budgetPreference === null
        ) {
            $styleGuideSession->clear();

            return $this->redirectToRoute('style_guide_index');
        }

        /*
        * Antwoorden die daadwerkelijk fysieke ruimte vragen.
        *
        * Het algemene antwoord "Laptop" heeft bewust geen
        * objectprofiel. De werkelijke afmetingen komen uit
        * de gekozen laptopmaat.
        */
        $fitAnswers = $carryItems;

        if ($laptopSize !== null) {
            $fitAnswers[] = $laptopSize;
        }

        /*
        * Technisch draagprofiel.
        */
        $bagFitProfile = $bagFitCalculator->calculate(
            $fitAnswers,
        );

        /*
        * Klantvriendelijke vertaling van het technische profiel.
        */
        $bagRecommendationProfile =
            $recommendationProfileCalculator->calculate(
                $bagFitProfile,
            );

        /*
        * Databasegestuurde criteria voor commerciële selectie
        * en verdere scoring.
        */
        $criteria = $criteriaFactory->create(
            targetAudience: $targetAudience,
            useMoment: $useMoment,
            styleWorld: $styleWorld,
            outfitPreference: $outfitPreference,
            carryItems: $carryItems,
            carryMethod: $carryMethod,
            materialPreference: $materialPreference,
            budgetPreference: $budgetPreference,
        );

        /*
        * Kandidaten zoeken, fysiek filteren, scoren en rangschikken.
        */
        $productMatches = $productMatcher->match(
            criteria: $criteria,
            fitProfile: $bagFitProfile,
            recommendationProfile: $bagRecommendationProfile,
            limit: 24,
        );

        $aiRecommendation = $aiAdvisor->advise(
            personalWish: $styleGuideSession->getPersonalWish(),
            criteria: $criteria,
            matches: $productMatches,
        );

        $productMatches = $aiRecommendation->matches;

        return $this->renderStyleGuide('style_guide/result.html.twig', [
            'useMoment' => $useMoment,
            'styleWorld' => $styleWorld,
            'outfitPreference' => $outfitPreference,
            'carryItems' => $carryItems,
            'laptopSize' => $laptopSize,
            'carryMethod' => $carryMethod,
            'materialPreference' => $materialPreference,
            'budgetPreference' => $budgetPreference,

            /*
            * Matcherresultaten.
            */
            'productMatches' => $productMatches,
            'aiRecommendation' => $aiRecommendation,
            'personalWish' => $styleGuideSession->getPersonalWish(),

            /*
            * Technisch profiel voor matching/debug.
            */
            'bagFitProfile' => $bagFitProfile,

            /*
            * Klantvriendelijk aanbevelingsprofiel.
            */
            'bagRecommendationProfile' =>
                $bagRecommendationProfile,

            /*
            * Alleen zichtbaar in dev/debug.
            */
            'debugBagFit' =>
                $this->getParameter('kernel.debug'),

            /*
            * Tijdelijke legacy-fallback.
            *
            * Deze kan verdwijnen zodra BagSizeCalculator en
            * de oude StyleGuideSession-formaatlogica zijn verwijderd.
            */
            'bagSizePreference' =>
                $styleGuideSession->getBagSizePreference(),
        ]);
    }

    #[Route('/opnieuw', name: 'restart', methods: ['POST'])]
    public function restart(
        Request $request,
        StyleGuideSession $styleGuideSession,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid(
            'style_guide_restart',
            (string) $request->request->get('_token'),
        )) {
            throw $this->createAccessDeniedException(
                'De beveiligingscontrole is mislukt.',
            );
        }

        $styleGuideSession->clear();

        return $this->redirectToRoute('style_guide_index');
    }

    /**
     * Rendert iedere stap van de Stijlgids binnen de bags-context.
     *
     * Hierdoor hoeven afzonderlijke actions niet zelf currentContext
     * en context door te geven en kan de navigatie tijdens de wizard
     * niet terugvallen naar de standaard shop-context.
     *
     * @param array<string, mixed> $parameters
     */
    private function renderStyleGuide(
        string $template,
        array $parameters = [],
    ): Response {
        return $this->render(
            $template,
            [
                'currentContext' => self::CONTEXT,
                'context' => self::CONTEXT,
                ...$parameters,
            ],
        );
    }
}
