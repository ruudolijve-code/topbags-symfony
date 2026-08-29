<?php

declare(strict_types=1);

namespace App\StyleGuide\Command;

use App\StyleGuide\Entity\StyleGuideAnswer;
use App\StyleGuide\Entity\StyleGuideAnswerWorldScore;
use App\StyleGuide\Entity\StyleGuideQuestion;
use App\StyleGuide\Entity\StyleGuideWorld;
use App\StyleGuide\Enum\SelectionType;
use App\StyleGuide\Repository\StyleGuideAnswerRepository;
use App\StyleGuide\Repository\StyleGuideQuestionRepository;
use App\StyleGuide\Repository\StyleGuideWorldRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:style-guide:initialize',
    description: 'Initialiseert de standaardconfiguratie van de Topbags Stijlgids.',
)]
final class InitializeStyleGuideCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StyleGuideWorldRepository $worldRepository,
        private readonly StyleGuideQuestionRepository $questionRepository,
        private readonly StyleGuideAnswerRepository $answerRepository,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $io->title('Topbags Stijlgids initialiseren');

        $worlds = $this->initializeWorlds();
        $this->initializeQuestions($worlds);

        $this->entityManager->flush();

        $io->success(
            'De standaardconfiguratie van de Stijlgids is aangemaakt/bijgewerkt.',
        );

        return Command::SUCCESS;
    }

    /**
     * @return array<string, StyleGuideWorld>
     */
    private function initializeWorlds(): array
    {
        $definitions = [
            'casual-chic' => [
                'name' => 'Casual Chic',
                'emotion' => 'Comfort',
                'motto' => 'Mijn tas moet overal bij passen.',
                'description' => 'Verzorgd, comfortabel en veelzijdig. Je zoekt een tas die moeiteloos bij je dagelijkse leven past.',
                'resultText' => 'Je kiest voor comfort, veelzijdigheid en een verzorgde uitstraling. Een tas moet mooi zijn, maar vooral moeiteloos aansluiten op jouw dagelijkse leven.',
                'position' => 10,
            ],
            'luxe-elegant' => [
                'name' => 'Luxe & Elegant',
                'emotion' => 'Zelfvertrouwen',
                'motto' => 'Kwaliteit zie je zonder dat het schreeuwt.',
                'description' => 'Verfijnd, stijlvol en bewust gekozen. Materiaal, afwerking en uitstraling mogen bijzonder zijn.',
                'resultText' => 'Je houdt van verfijning, kwaliteit en een stijlvolle uitstraling. Je kiest bewust en waardeert mooie materialen en hoogwaardige afwerking.',
                'position' => 20,
            ],
            'bohemian-kleurrijk' => [
                'name' => 'Bohemian & Kleurrijk',
                'emotion' => 'Vrijheid',
                'motto' => 'Mijn tas mag net zo vrolijk zijn als ik.',
                'description' => 'Creatief, speels en persoonlijk. Je tas mag kleur hebben en laten zien wie je bent.',
                'resultText' => 'Je houdt van kleur, creativiteit en een ontspannen uitstraling. Jouw tas mag karakter hebben en iets van jouw persoonlijkheid laten zien.',
                'position' => 30,
            ],
            'naturel-tijdloos' => [
                'name' => 'Naturel & Tijdloos',
                'emotion' => 'Rust',
                'motto' => 'Ik koop liever één goede tas dan drie nieuwe.',
                'description' => 'Authentiek, rustig en duurzaam. Je kiest graag voor natuurlijke materialen en tijdloze kleuren.',
                'resultText' => 'Je kiest bewust voor rust, authenticiteit en kwaliteit die lang meegaat. Natuurlijke materialen en tijdloze vormen spreken je aan.',
                'position' => 40,
            ],
            'klassiek-verzorgd' => [
                'name' => 'Klassiek & Verzorgd',
                'emotion' => 'Zekerheid',
                'motto' => 'Een goede tas moet je iedere dag kunnen vertrouwen.',
                'description' => 'Betrouwbaar, overzichtelijk en verzorgd. Een tas moet praktisch zijn en jarenlang meegaan.',
                'resultText' => 'Je waardeert overzicht, betrouwbaarheid en een verzorgde uitstraling. Functionaliteit en kwaliteit zijn voor jou minstens zo belangrijk als mode.',
                'position' => 50,
            ],
            'fashion-forward' => [
                'name' => 'Fashion Forward',
                'emotion' => 'Inspiratie',
                'motto' => 'Mijn stijl verandert met het seizoen.',
                'description' => 'Eigentijds, modieus en vernieuwend. Je tas maakt je outfit compleet en mag de trends volgen.',
                'resultText' => 'Je volgt graag nieuwe trends en gebruikt accessoires om jouw outfit steeds opnieuw vorm te geven. Mode mag zichtbaar en verrassend zijn.',
                'position' => 60,
            ],
        ];

        $worlds = [];

        foreach ($definitions as $slug => $definition) {
            $world = $this->worldRepository->findOneBy([
                'slug' => $slug,
            ]);

            if ($world === null) {
                $world = new StyleGuideWorld();
                $world->setSlug($slug);

                $this->entityManager->persist($world);
            }

            $world
                ->setName($definition['name'])
                ->setEmotion($definition['emotion'])
                ->setMotto($definition['motto'])
                ->setDescription($definition['description'])
                ->setResultText($definition['resultText'])
                ->setPosition($definition['position'])
                ->setIsActive(true);

            $worlds[$slug] = $world;
        }

        $this->entityManager->flush();

        return $worlds;
    }

    /**
     * @param array<string, StyleGuideWorld> $worlds
     */
    private function initializeQuestions(array $worlds): void
    {
        $definitions = [
            [
                'code' => 'target_audience',
                'title' => 'Voor wie zoek je een tas?',
                'description' => 'Kies een doelgroep, of laat alle tassen meenemen.',
                'selectionType' => SelectionType::SINGLE,
                'position' => 5,
                'answers' => [
                    ['dames', 'Dames', 10],
                    ['heren', 'Heren', 20],
                    ['geen-voorkeur', 'Geen voorkeur', 30],
                ],
            ],
            [
                'code' => 'use_moment',
                'title' => 'Waarvoor zoek je vooral een tas?',
                'description' => 'Kies het gebruiksmoment dat voor jou het belangrijkst is.',
                'selectionType' => SelectionType::SINGLE,
                'position' => 10,
                'answers' => [
                    ['dagelijks', 'Iedere dag', 10],
                    ['werk', 'Werk & zakelijk', 20],
                    ['winkelen', 'Winkelen & vrije tijd', 30],
                    ['gelegenheid', 'Feestelijke gelegenheid', 40],
                    ['reizen', 'Dagje weg & reizen', 50],
                    ['avond', 'Avondje uit', 60],
                ],
            ],

            [
                'code' => 'style_world',
                'title' => 'Welke stijl voelt het meest als jij?',
                'description' => 'Kies de stijlwereld waarin jij jezelf het beste herkent.',
                'selectionType' => SelectionType::SINGLE,
                'position' => 20,
                'answers' => [
                    [
                        'casual-chic',
                        'Casual Chic',
                        10,
                        ['casual-chic' => 100],
                    ],
                    [
                        'luxe-elegant',
                        'Luxe & Elegant',
                        20,
                        ['luxe-elegant' => 100],
                    ],
                    [
                        'bohemian-kleurrijk',
                        'Bohemian & Kleurrijk',
                        30,
                        ['bohemian-kleurrijk' => 100],
                    ],
                    [
                        'naturel-tijdloos',
                        'Naturel & Tijdloos',
                        40,
                        ['naturel-tijdloos' => 100],
                    ],
                    [
                        'klassiek-verzorgd',
                        'Klassiek & Verzorgd',
                        50,
                        ['klassiek-verzorgd' => 100],
                    ],
                    [
                        'fashion-forward',
                        'Fashion Forward',
                        60,
                        ['fashion-forward' => 100],
                    ],
                ],
            ],

            [
                'code' => 'outfit',
                'title' => 'Welke outfits passen het beste bij jou?',
                'description' => 'Kies de kledingstijl en accessoires waarin jij jezelf het beste herkent.',
                'selectionType' => SelectionType::SINGLE,
                'position' => 30,
                'answers' => [
                    [
                        'tijdloze-basics',
                        'Tijdloze basics',
                        10,
                        [
                            'casual-chic' => 30,
                            'klassiek-verzorgd' => 10,
                        ],
                    ],
                    [
                        'luxe-verfijnd',
                        'Luxe & verfijnd',
                        20,
                        [
                            'luxe-elegant' => 30,
                            'klassiek-verzorgd' => 20,
                        ],
                    ],
                    [
                        'kleurrijk-creatief',
                        'Kleurrijk & creatief',
                        30,
                        [
                            'bohemian-kleurrijk' => 30,
                            'fashion-forward' => 10,
                        ],
                    ],
                    [
                        'naturel-ontspannen',
                        'Naturel & ontspannen',
                        40,
                        [
                            'naturel-tijdloos' => 30,
                            'casual-chic' => 10,
                        ],
                    ],
                    [
                        'trendy-wisselend',
                        'Trendy & wisselend',
                        50,
                        [
                            'fashion-forward' => 30,
                            'luxe-elegant' => 10,
                        ],
                    ],
                ],
            ],

            [
                'code' => 'carry_items',
                'title' => 'Wat neem je meestal mee?',
                'description' => 'Je kunt meerdere antwoorden kiezen. Hiermee bepalen we welk formaat tas het beste bij je past.',
                'selectionType' => SelectionType::MULTIPLE,
                'position' => 40,
                'answers' => [
                    ['telefoon', 'Telefoon', 10],
                    ['portemonnee', 'Portemonnee', 20],
                    ['waterfles', 'Waterfles', 30],
                    ['tablet', 'Tablet', 40],
                    ['laptop', 'Laptop', 50],
                    ['a4-documenten', 'A4-documenten', 60],
                    ['veel-spullen', 'Veel spullen', 70],
                ],
            ],

            [
                'code' => 'carry_method',
                'title' => 'Hoe draag je jouw tas?',
                'description' => 'Kies de draagwijze die het beste aansluit bij jouw dagelijkse gebruik.',
                'selectionType' => SelectionType::SINGLE,
                'position' => 50,
                'answers' => [
                    ['rugzak', 'Rugzak', 10],
                    ['crossbody', 'Crossbody', 20],
                    ['schoudertas', 'Schoudertas', 30],
                    ['handtas', 'Handtas', 40],
                    ['shopper', 'Shopper', 50],
                ],
            ],

            [
                'code' => 'material',
                'title' => 'Welk materiaal past het beste bij jou?',
                'description' => 'Kies het materiaal dat je het mooist of prettigst vindt.',
                'selectionType' => SelectionType::SINGLE,
                'position' => 60,
                'answers' => [
                    ['leer', 'Leer', 10],
                    ['vegan', 'Vegan', 20],
                    ['canvas', 'Canvas', 30],
                    ['nylon', 'Nylon', 40],
                    ['geen-voorkeur', 'Geen voorkeur', 50],
                ],
            ],

            [
                'code' => 'budget',
                'title' => 'Welk prijsniveau past bij jou?',
                'description' => 'Kies wat jij belangrijk vindt. De juiste tas mag ook belangrijker zijn dan een vaste prijsklasse.',
                'selectionType' => SelectionType::SINGLE,
                'position' => 70,
                'answers' => [
                    [
                        'aantrekkelijke-prijs',
                        'Een mooie tas voor een aantrekkelijke prijs',
                        10,
                    ],
                    [
                        'goede-kwaliteit',
                        'Goede kwaliteit voor dagelijks gebruik',
                        20,
                    ],
                    [
                        'premium',
                        'Premium materialen en afwerking',
                        30,
                    ],
                    [
                        'luxe',
                        'Alleen het beste',
                        40,
                    ],
                    [
                        'geen-voorkeur',
                        'Geen voorkeur',
                        50,
                    ],
                ],
            ],
        ];

        foreach ($definitions as $questionDefinition) {
            $question = $this->questionRepository->findOneBy([
                'code' => $questionDefinition['code'],
            ]);

            if ($question === null) {
                $question = new StyleGuideQuestion();
                $question->setCode($questionDefinition['code']);

                $this->entityManager->persist($question);
            }

            $question
                ->setTitle($questionDefinition['title'])
                ->setDescription($questionDefinition['description'])
                ->setSelectionType(
                    $questionDefinition['selectionType'],
                )
                ->setPosition($questionDefinition['position'])
                ->setIsActive(true);

            foreach ($questionDefinition['answers'] as $answerDefinition) {
                $this->initializeAnswer(
                    $question,
                    $answerDefinition,
                    $worlds,
                );
            }
        }
    }

    /**
     * @param array<int, mixed> $definition
     * @param array<string, StyleGuideWorld> $worlds
     */
    private function initializeAnswer(
        StyleGuideQuestion $question,
        array $definition,
        array $worlds,
    ): void {
        [$code, $label, $position] = $definition;

        $answer = $this->answerRepository->findOneBy([
            'question' => $question,
            'code' => $code,
        ]);

        if ($answer === null) {
            $answer = new StyleGuideAnswer();

            $answer
                ->setQuestion($question)
                ->setCode($code);

            $this->entityManager->persist($answer);
        }

        $answer
            ->setLabel($label)
            ->setPosition($position)
            ->setIsActive(true);

        $scores = $definition[3] ?? [];

        foreach ($scores as $worldSlug => $scoreValue) {
            $world = $worlds[$worldSlug] ?? null;

            if ($world === null) {
                continue;
            }

            $existingScore = null;

            foreach ($answer->getWorldScores() as $worldScore) {
                if ($worldScore->getStyleWorld() === $world) {
                    $existingScore = $worldScore;
                    break;
                }
            }

            if ($existingScore === null) {
                $existingScore = new StyleGuideAnswerWorldScore();

                $existingScore
                    ->setAnswer($answer)
                    ->setStyleWorld($world);

                $this->entityManager->persist($existingScore);
            }

            $existingScore->setScore((int) $scoreValue);
        }
    }
}
