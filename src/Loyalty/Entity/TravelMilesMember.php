<?php

declare(strict_types=1);

namespace App\Loyalty\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'travel_miles_member')]
#[ORM\UniqueConstraint(name: 'UNIQ_TRAVEL_MILES_MEMBER_EMAIL', columns: ['email'])]
class TravelMilesMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateOfBirth = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $street = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $houseNumber = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 2, options: ['default' => 'NL'])]
    private string $country = 'NL';

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $voucherSent = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $source = null;

    #[ORM\Column]
    private \DateTimeImmutable $consentGivenAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $postalMailConsentAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->consentGivenAt = $now;
    }

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

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $this->normalizeNullableString($firstName);

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $this->normalizeNullableString($lastName);

        return $this;
    }

    public function getFullName(): string
    {
        return trim(sprintf(
            '%s %s',
            $this->firstName ?? '',
            $this->lastName ?? ''
        ));
    }

    public function getDateOfBirth(): ?\DateTimeImmutable
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeImmutable $dateOfBirth): self
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $this->normalizeNullableString($street);

        return $this;
    }

    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    public function setHouseNumber(?string $houseNumber): self
    {
        $this->houseNumber = $this->normalizeNullableString($houseNumber);

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $postalCode = $this->normalizeNullableString($postalCode);

        $this->postalCode = $postalCode !== null
            ? strtoupper(str_replace(' ', '', $postalCode))
            : null;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $this->normalizeNullableString($city);

        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $country = strtoupper(trim($country));

        $this->country = $country !== '' ? mb_substr($country, 0, 2) : 'NL';

        return $this;
    }

    public function hasPostalAddress(): bool
    {
        return $this->street !== null
            && $this->houseNumber !== null
            && $this->postalCode !== null
            && $this->city !== null;
    }

    public function getPostalAddressLine(): string
    {
        return trim(sprintf(
            '%s %s, %s %s',
            $this->street ?? '',
            $this->houseNumber ?? '',
            $this->postalCode ?? '',
            $this->city ?? ''
        ));
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

    public function isVoucherSent(): bool
    {
        return $this->voucherSent;
    }

    public function setVoucherSent(bool $voucherSent): self
    {
        $this->voucherSent = $voucherSent;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $this->normalizeNullableString($source);

        return $this;
    }

    public function getConsentGivenAt(): \DateTimeImmutable
    {
        return $this->consentGivenAt;
    }

    public function setConsentGivenAt(\DateTimeImmutable $consentGivenAt): self
    {
        $this->consentGivenAt = $consentGivenAt;

        return $this;
    }

    public function getPostalMailConsentAt(): ?\DateTimeImmutable
    {
        return $this->postalMailConsentAt;
    }

    public function setPostalMailConsentAt(?\DateTimeImmutable $postalMailConsentAt): self
    {
        $this->postalMailConsentAt = $postalMailConsentAt;

        return $this;
    }

    public function hasPostalMailConsent(): bool
    {
        return $this->postalMailConsentAt !== null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    private function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}