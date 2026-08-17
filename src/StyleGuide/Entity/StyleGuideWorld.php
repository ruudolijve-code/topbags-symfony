<?php

declare(strict_types=1);

namespace App\StyleGuide\Entity;

use App\StyleGuide\Repository\StyleGuideWorldRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StyleGuideWorldRepository::class)]
#[ORM\Table(name: 'style_guide_world')]
class StyleGuideWorld
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(length: 100, unique: true)]
    private string $slug = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $emotion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motto = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resultText = null;

    #[ORM\Column(type: 'smallint', options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    /**
     * @var Collection<int, StyleGuideAnswerWorldScore>
     */
    #[ORM\OneToMany(
        mappedBy: 'styleWorld',
        targetEntity: StyleGuideAnswerWorldScore::class,
        orphanRemoval: true,
    )]
    private Collection $answerScores;

    public function __construct()
    {
        $this->answerScores = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name !== ''
            ? $this->name
            : sprintf('Stijlwereld #%s', $this->id ?? 'nieuw');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = trim($slug);

        return $this;
    }

    public function getEmotion(): ?string
    {
        return $this->emotion;
    }

    public function setEmotion(?string $emotion): self
    {
        $emotion = $emotion !== null ? trim($emotion) : null;
        $this->emotion = $emotion !== '' ? $emotion : null;

        return $this;
    }

    public function getMotto(): ?string
    {
        return $this->motto;
    }

    public function setMotto(?string $motto): self
    {
        $motto = $motto !== null ? trim($motto) : null;
        $this->motto = $motto !== '' ? $motto : null;

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

    public function getResultText(): ?string
    {
        return $this->resultText;
    }

    public function setResultText(?string $resultText): self
    {
        $resultText = $resultText !== null
            ? trim($resultText)
            : null;

        $this->resultText = $resultText !== ''
            ? $resultText
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
    public function getAnswerScores(): Collection
    {
        return $this->answerScores;
    }

    public function addAnswerScore(
        StyleGuideAnswerWorldScore $answerScore,
    ): self {
        if (!$this->answerScores->contains($answerScore)) {
            $this->answerScores->add($answerScore);
            $answerScore->setStyleWorld($this);
        }

        return $this;
    }

    public function removeAnswerScore(
        StyleGuideAnswerWorldScore $answerScore,
    ): self {
        if (
            $this->answerScores->removeElement($answerScore)
            && $answerScore->getStyleWorld() === $this
        ) {
            $answerScore->setStyleWorld(null);
        }

        return $this;
    }
}