<?php

declare(strict_types=1);

namespace App\StyleGuide\Entity;

use App\StyleGuide\Repository\StyleGuideAnswerWorldScoreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(
    repositoryClass: StyleGuideAnswerWorldScoreRepository::class,
)]
#[ORM\Table(
    name: 'style_guide_answer_world_score',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'uniq_style_guide_answer_world',
            columns: ['answer_id', 'style_world_id'],
        ),
    ],
)]
class StyleGuideAnswerWorldScore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(
        inversedBy: 'worldScores',
        targetEntity: StyleGuideAnswer::class,
    )]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StyleGuideAnswer $answer = null;

    #[ORM\ManyToOne(
        inversedBy: 'answerScores',
        targetEntity: StyleGuideWorld::class,
    )]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StyleGuideWorld $styleWorld = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $score = 0;

    public function __toString(): string
    {
        $answer = $this->answer?->getLabel() ?? 'Onbekend antwoord';
        $world = $this->styleWorld?->getName() ?? 'Onbekende stijl';

        return sprintf('%s → %s: %d', $answer, $world, $this->score);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnswer(): ?StyleGuideAnswer
    {
        return $this->answer;
    }

    public function setAnswer(?StyleGuideAnswer $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function getStyleWorld(): ?StyleGuideWorld
    {
        return $this->styleWorld;
    }

    public function setStyleWorld(
        ?StyleGuideWorld $styleWorld,
    ): self {
        $this->styleWorld = $styleWorld;

        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;

        return $this;
    }
}