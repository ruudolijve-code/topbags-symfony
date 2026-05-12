<?php

declare(strict_types=1);

namespace App\Admin\Entity;

use App\Admin\Repository\AdminUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: AdminUserRepository::class)]
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    public const ROLE_ADMIN_USER = 'ROLE_ADMIN_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_STORE = 'ROLE_STORE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email = '';

    /**
     * @var string[]
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleAuthenticatorSecret = null;

    public function __toString(): string
    {
        return $this->email;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // Basisrol voor iedereen die in de adminomgeving mag inloggen.
        // Niet automatisch ROLE_ADMIN toevoegen, anders krijgt een winkelgebruiker volledige rechten.
        $roles[] = self::ROLE_ADMIN_USER;

        return array_values(array_unique($roles));
    }

    /**
     * Dit zijn alleen de expliciet opgeslagen rollen.
     * Handig voor EasyAdmin, zodat ROLE_ADMIN_USER niet steeds als opgeslagen rol verschijnt.
     *
     * @return string[]
     */
    public function getStoredRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): static
    {
        $allowedRoles = [
            self::ROLE_ADMIN,
            self::ROLE_STORE,
        ];

        $this->roles = array_values(array_unique(array_filter(
            $roles,
            static fn (mixed $role): bool => is_string($role) && in_array($role, $allowedRoles, true)
        )));

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function isFullAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isStoreUser(): bool
    {
        return $this->hasRole(self::ROLE_STORE);
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->googleAuthenticatorSecret;
    }

    public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): static
    {
        $googleAuthenticatorSecret = $googleAuthenticatorSecret !== null
            ? trim($googleAuthenticatorSecret)
            : null;

        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret !== '' ? $googleAuthenticatorSecret : null;

        return $this;
    }

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return $this->googleAuthenticatorSecret !== null;
    }

    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function eraseCredentials(): void
    {
        // niets nodig voor nu
    }
}