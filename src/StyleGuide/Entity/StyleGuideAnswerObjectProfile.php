<?php

declare(strict_types=1);

namespace App\StyleGuide\Entity;

use App\StyleGuide\Repository\StyleGuideAnswerObjectProfileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(
    repositoryClass: StyleGuideAnswerObjectProfileRepository::class,
)]
#[ORM\Table(name: 'style_guide_answer_object_profile')]
#[ORM\UniqueConstraint(
    name: 'uniq_style_guide_answer_object_profile',
    columns: ['answer_id', 'object_profile_id'],
)]
class StyleGuideAnswerObjectProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StyleGuideAnswer::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StyleGuideAnswer $answer = null;

    #[ORM\ManyToOne(targetEntity: StyleGuideObjectProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StyleGuideObjectProfile $objectProfile = null;

    #[ORM\Column(options: ['default' => 100])]
    private int $weight = 100;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

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

    public function getObjectProfile(): ?StyleGuideObjectProfile
    {
        return $this->objectProfile;
    }

    public function setObjectProfile(
        ?StyleGuideObjectProfile $objectProfile,
    ): self {
        $this->objectProfile = $objectProfile;

        return $this;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): self
    {
        $this->weight = max(0, $weight);

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
}