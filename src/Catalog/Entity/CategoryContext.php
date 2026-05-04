<?php

namespace App\Catalog\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'category_context')]
class CategoryContext
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'contexts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Category $category;

    #[ORM\Id]
    #[ORM\Column(length: 20)]
    private string $context;

    #[ORM\Column]
    private int $position = 0;

    public function __toString(): string
    {
        return $this->context . ' #' . $this->position;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function setContext(string $context): self
    {
        $this->context = $context;

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
}