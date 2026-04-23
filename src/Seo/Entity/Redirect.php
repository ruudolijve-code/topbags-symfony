<?php

namespace App\Seo\Entity;

use App\Seo\Repository\RedirectRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RedirectRepository::class)]
#[ORM\Table(name: 'redirect')]
#[ORM\UniqueConstraint(name: 'uniq_redirect_old_path', columns: ['old_path'])]
class Redirect
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'old_path', length: 500)]
    private string $oldPath;

    #[ORM\Column(name: 'new_url', length: 500)]
    private string $newUrl;

    #[ORM\Column(name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOldPath(): string
    {
        return $this->oldPath;
    }

    public function setOldPath(string $oldPath): self
    {
        $this->oldPath = $oldPath;

        return $this;
    }

    public function getNewUrl(): string
    {
        return $this->newUrl;
    }

    public function setNewUrl(string $newUrl): self
    {
        $this->newUrl = $newUrl;

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