<?php

declare(strict_types=1);

namespace App\StyleGuide\Entity;

use App\StyleGuide\Enum\SelectionType;
use App\StyleGuide\Repository\StyleGuideQuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StyleGuideQuestionRepository::class)]
#[ORM\Table(name: 'style_guide_question')]
class StyleGuideQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $code = '';

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $helpText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 20,
        enumType: SelectionType::class,
    )]
    private SelectionType $selectionType = SelectionType::SINGLE;

    #[ORM\Column(
        type: Types::SMALLINT,
        options: ['default' => 0],
    )]
    private int $position = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    /**
     * @var Collection<int, StyleGuideAnswer>
     */
    #[ORM\OneToMany(
        mappedBy: 'question',
        targetEntity: StyleGuideAnswer::class,
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy([
        'position' => 'ASC',
        'id' => 'ASC',
    ])]
    private Collection $answers;

    public function __construct()
    {
        $this->answers = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title !== ''
            ? $this->title
            : sprintf(
                'Vraag #%s',
                $this->id ?? 'nieuw',
            );
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): self
    {
        $subtitle = $subtitle !== null
            ? trim($subtitle)
            : null;

        $this->subtitle = $subtitle !== ''
            ? $subtitle
            : null;

        return $this;
    }

    public function getHelpText(): ?string
    {
        return $this->helpText;
    }

    public function setHelpText(?string $helpText): self
    {
        $helpText = $helpText !== null
            ? trim($helpText)
            : null;

        $this->helpText = $helpText !== ''
            ? $helpText
            : null;

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

    public function getSelectionType(): SelectionType
    {
        return $this->selectionType;
    }

    public function setSelectionType(
        SelectionType $selectionType,
    ): self {
        $this->selectionType = $selectionType;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = max(
            0,
            $position,
        );

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
     * @return Collection<int, StyleGuideAnswer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(
        StyleGuideAnswer $answer,
    ): self {
        if (!$this->answers->contains($answer)) {
            $this->answers->add($answer);
            $answer->setQuestion($this);
        }

        return $this;
    }

    public function removeAnswer(
        StyleGuideAnswer $answer,
    ): self {
        $this->answers->removeElement($answer);

        return $this;
    }
}