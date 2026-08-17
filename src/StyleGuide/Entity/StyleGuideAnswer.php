<?php

declare(strict_types=1);

namespace App\StyleGuide\Entity;

use App\StyleGuide\Repository\StyleGuideAnswerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StyleGuideAnswerRepository::class)]
#[ORM\Table(
    name: 'style_guide_answer',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'uniq_style_guide_answer_question_code',
            columns: ['question_id', 'code'],
        ),
    ],
)]
class StyleGuideAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(
        inversedBy: 'answers',
        targetEntity: StyleGuideQuestion::class,
    )]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StyleGuideQuestion $question = null;

    #[ORM\Column(length: 100)]
    private string $code = '';

    #[ORM\Column(length: 255)]
    private string $label = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'smallint', options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    /**
     * @var Collection<int, StyleGuideAnswerWorldScore>
     */
    #[ORM\OneToMany(
        mappedBy: 'answer',
        targetEntity: StyleGuideAnswerWorldScore::class,
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    private Collection $worldScores;

    public function __construct()
    {
        $this->worldScores = new ArrayCollection();
    }

    public function __toString(): string
    {
        if ($this->question !== null) {
            return sprintf(
                '%s — %s',
                $this->question->getTitle(),
                $this->label,
            );
        }

        return $this->label !== ''
            ? $this->label
            : sprintf('Antwoord #%s', $this->id ?? 'nieuw');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?StyleGuideQuestion
    {
        return $this->question;
    }

    public function setQuestion(
        ?StyleGuideQuestion $question,
    ): self {
        $this->question = $question;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = trim($code);

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = trim($label);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $description = $description !== null
            ? trim($description)
            : null;

        $this->description = $description !== ''
            ? $description
            : null;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = max(0, $position);

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, StyleGuideAnswerWorldScore>
     */
    public function getWorldScores(): Collection
    {
        return $this->worldScores;
    }

    public function addWorldScore(
        StyleGuideAnswerWorldScore $worldScore,
    ): self {
        if (!$this->worldScores->contains($worldScore)) {
            $this->worldScores->add($worldScore);
            $worldScore->setAnswer($this);
        }

        return $this;
    }

    public function removeWorldScore(
        StyleGuideAnswerWorldScore $worldScore,
    ): self {
        if (
            $this->worldScores->removeElement($worldScore)
            && $worldScore->getAnswer() === $this
        ) {
            $worldScore->setAnswer(null);
        }

        return $this;
    }
}