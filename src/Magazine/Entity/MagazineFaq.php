<?php

declare(strict_types=1);

namespace App\Magazine\Entity;

use App\Magazine\Repository\MagazineFaqRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MagazineFaqRepository::class)]
#[ORM\Table(name: 'magazine_faq')]
class MagazineFaq
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MagazineArticle::class, inversedBy: 'faqs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MagazineArticle $article = null;

    #[ORM\Column(length: 255)]
    private string $question = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $answer = '';

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $isActive = true;

    public function __toString(): string
    {
        return $this->question ?: 'Nieuwe FAQ';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticle(): ?MagazineArticle
    {
        return $this->article;
    }

    public function setArticle(?MagazineArticle $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = trim($question);

        return $this;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): self
    {
        $this->answer = trim($answer);

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }
}