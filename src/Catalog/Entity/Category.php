<?php

namespace App\Catalog\Entity;

use App\Catalog\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'category')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 255, unique: true)]
    private string $slug = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $menuLabel = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $introDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $seoDescription = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $showInMenu = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $allowsPersonal = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $allowsCabin = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $allowsHold = false;

    #[ORM\Column(options: ['default' => true])]
    private bool $transportPlane = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $transportCar = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $transportTrain = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $transportBus = true;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\ManyToMany(mappedBy: 'categories', targetEntity: Product::class)]
    private Collection $products;

    /**
     * @var Collection<int, CategoryContext>
     */
    #[ORM\OneToMany(
        mappedBy: 'category',
        targetEntity: CategoryContext::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $contexts;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->contexts = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->menuLabel ?: $this->name;
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
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getMenuLabel(): ?string
    {
        return $this->menuLabel;
    }

    public function setMenuLabel(?string $menuLabel): self
    {
        $this->menuLabel = $menuLabel;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getIntroDescription(): ?string
    {
        return $this->introDescription;
    }

    public function setIntroDescription(?string $introDescription): self
    {
        $this->introDescription = $introDescription;

        return $this;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    public function setSeoDescription(?string $seoDescription): self
    {
        $this->seoDescription = $seoDescription;

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

    public function isShowInMenu(): bool
    {
        return $this->showInMenu;
    }

    public function setShowInMenu(bool $showInMenu): self
    {
        $this->showInMenu = $showInMenu;

        return $this;
    }

    public function isAllowsPersonal(): bool
    {
        return $this->allowsPersonal;
    }

    public function setAllowsPersonal(bool $allowsPersonal): self
    {
        $this->allowsPersonal = $allowsPersonal;

        return $this;
    }

    public function isAllowsCabin(): bool
    {
        return $this->allowsCabin;
    }

    public function setAllowsCabin(bool $allowsCabin): self
    {
        $this->allowsCabin = $allowsCabin;

        return $this;
    }

    public function isAllowsHold(): bool
    {
        return $this->allowsHold;
    }

    public function setAllowsHold(bool $allowsHold): self
    {
        $this->allowsHold = $allowsHold;

        return $this;
    }

    public function isTransportPlane(): bool
    {
        return $this->transportPlane;
    }

    public function setTransportPlane(bool $transportPlane): self
    {
        $this->transportPlane = $transportPlane;

        return $this;
    }

    public function isTransportCar(): bool
    {
        return $this->transportCar;
    }

    public function setTransportCar(bool $transportCar): self
    {
        $this->transportCar = $transportCar;

        return $this;
    }

    public function isTransportTrain(): bool
    {
        return $this->transportTrain;
    }

    public function setTransportTrain(bool $transportTrain): self
    {
        $this->transportTrain = $transportTrain;

        return $this;
    }

    public function isTransportBus(): bool
    {
        return $this->transportBus;
    }

    public function setTransportBus(bool $transportBus): self
    {
        $this->transportBus = $transportBus;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        if ($parent === $this) {
            return $this;
        }

        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child) && $child->getParent() === $this) {
            $child->setParent(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->addCategory($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            $product->removeCategory($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, CategoryContext>
     */
    public function getContexts(): Collection
    {
        return $this->contexts;
    }

    public function addContext(CategoryContext $context): self
    {
        if (!$this->contexts->contains($context)) {
            $this->contexts->add($context);
            $context->setCategory($this);
        }

        return $this;
    }

    public function removeContext(CategoryContext $context): self
    {
        $this->contexts->removeElement($context);

        return $this;
    }

    public function hasContext(string $context): bool
    {
        return $this->findContext($context) !== null;
    }

    /**
     * @return string[]
     */
    public function getContextValues(): array
    {
        $values = [];

        foreach ($this->contexts as $categoryContext) {
            $values[] = $categoryContext->getContext();
        }

        return $values;
    }

    public function getPrimaryContext(): ?string
    {
        $first = $this->contexts->first();

        if ($first === false) {
            return null;
        }

        return $first->getContext();
    }

    public function isShopContext(): bool
    {
        return $this->hasContext(Product::CONTEXT_SHOP);
    }

    public function setShopContext(bool $enabled): self
    {
        return $this->setContextEnabled(Product::CONTEXT_SHOP, $enabled);
    }

    public function isBagsContext(): bool
    {
        return $this->hasContext(Product::CONTEXT_BAGS);
    }

    public function setBagsContext(bool $enabled): self
    {
        return $this->setContextEnabled(Product::CONTEXT_BAGS, $enabled);
    }

    public function getShopMenuPosition(): ?int
    {
        return $this->getContextPosition(Product::CONTEXT_SHOP);
    }

    public function setShopMenuPosition(?int $position): self
    {
        return $this->setContextPosition(Product::CONTEXT_SHOP, $position);
    }

    public function getBagsMenuPosition(): ?int
    {
        return $this->getContextPosition(Product::CONTEXT_BAGS);
    }

    public function setBagsMenuPosition(?int $position): self
    {
        return $this->setContextPosition(Product::CONTEXT_BAGS, $position);
    }

    private function findContext(string $context): ?CategoryContext
    {
        foreach ($this->contexts as $categoryContext) {
            if ($categoryContext->getContext() === $context) {
                return $categoryContext;
            }
        }

        return null;
    }

    private function setContextEnabled(string $context, bool $enabled): self
    {
        $existingContext = $this->findContext($context);

        if ($enabled && $existingContext === null) {
            $categoryContext = new CategoryContext();
            $categoryContext->setContext($context);
            $categoryContext->setPosition($this->position);
            $this->addContext($categoryContext);
        }

        if (!$enabled && $existingContext !== null) {
            $this->removeContext($existingContext);
        }

        return $this;
    }

    private function getContextPosition(string $context): ?int
    {
        return $this->findContext($context)?->getPosition();
    }

    private function setContextPosition(string $context, ?int $position): self
    {
        $categoryContext = $this->findContext($context);

        if ($categoryContext === null) {
            $categoryContext = new CategoryContext();
            $categoryContext->setContext($context);
            $this->addContext($categoryContext);
        }

        $categoryContext->setPosition((int) ($position ?? 0));

        return $this;
    }
}