<?php

declare(strict_types=1);

namespace App\Magazine\Entity;

use App\Catalog\Entity\Brand;
use App\Catalog\Entity\Category;
use App\Catalog\Entity\Product;
use App\Magazine\Repository\MagazineArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MagazineArticleRepository::class)]
#[ORM\Table(name: 'magazine_article')]
#[ORM\UniqueConstraint(
    name: 'uniq_magazine_article_context_slug',
    columns: ['context', 'slug']
)]
#[ORM\Index(
    name: 'idx_magazine_article_context_published',
    columns: ['context', 'is_published', 'published_at']
)]
#[ORM\HasLifecycleCallbacks]
class MagazineArticle
{
    public const CONTEXT_SHOP = 'shop';
    public const CONTEXT_BAGS = 'bags';

    public const CONTEXTS = [
        self::CONTEXT_SHOP,
        self::CONTEXT_BAGS,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Bepaalt in welk magazine het artikel verschijnt:
     *
     * - shop: koffers, reizen, bagage en vliegadvies
     * - bags: tassen, rugzakken, accessoires en onderhoud
     */
    #[ORM\Column(length: 20, options: ['default' => self::CONTEXT_SHOP])]
    private string $context = self::CONTEXT_SHOP;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 255)]
    private string $slug = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $seoTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $seoDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $excerpt = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $heroImage = null;

    #[ORM\Column]
    private bool $isFeatured = false;

    #[ORM\Column]
    private bool $isPublished = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /**
     * Productcategorie die inhoudelijk aansluit bij het artikel.
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $relatedCategory = null;

    /**
     * Tijdelijk legacyveld voor de migratie naar relatedBrands.
     *
     * @deprecated Alleen behouden totdat bestaande merkgegevens zijn overgezet.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $relatedBrandSlug = null;

    /**
     * @var Collection<int, MagazineFaq>
     */
    #[ORM\OneToMany(
        mappedBy: 'article',
        targetEntity: MagazineFaq::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $faqs;

    /**
     * Merken die inhoudelijk aansluiten bij het artikel.
     *
     * @var Collection<int, Brand>
     */
    #[ORM\ManyToMany(targetEntity: Brand::class)]
    #[ORM\JoinTable(name: 'magazine_article_brand')]
    #[ORM\JoinColumn(
        name: 'magazine_article_id',
        referencedColumnName: 'id',
        onDelete: 'CASCADE'
    )]
    #[ORM\InverseJoinColumn(
        name: 'brand_id',
        referencedColumnName: 'id',
        onDelete: 'CASCADE'
    )]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $relatedBrands;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\ManyToMany(targetEntity: Product::class)]
    #[ORM\JoinTable(name: 'magazine_article_product')]
    #[ORM\JoinColumn(
        name: 'magazine_article_id',
        referencedColumnName: 'id',
        onDelete: 'CASCADE'
    )]
    #[ORM\InverseJoinColumn(
        name: 'product_id',
        referencedColumnName: 'id',
        onDelete: 'CASCADE'
    )]
    private Collection $relatedProducts;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->faqs = new ArrayCollection();
        $this->relatedBrands = new ArrayCollection();
        $this->relatedProducts = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title ?: 'Nieuw magazineartikel';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function setContext(string $context): self
    {
        $context = strtolower(trim($context));

        if (!in_array($context, self::CONTEXTS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Ongeldige magazinecontext "%s". Toegestaan zijn: %s.',
                $context,
                implode(', ', self::CONTEXTS)
            ));
        }

        $this->context = $context;

        return $this;
    }

    public function isShopContext(): bool
    {
        return $this->context === self::CONTEXT_SHOP;
    }

    public function isBagsContext(): bool
    {
        return $this->context === self::CONTEXT_BAGS;
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = trim($slug);

        return $this;
    }

    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    public function setSeoTitle(?string $seoTitle): self
    {
        $this->seoTitle = $seoTitle ? trim($seoTitle) : null;

        return $this;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    public function setSeoDescription(?string $seoDescription): self
    {
        $this->seoDescription = $seoDescription
            ? trim($seoDescription)
            : null;

        return $this;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): self
    {
        $this->excerpt = $excerpt ? trim($excerpt) : null;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = trim($content);

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category ? trim($category) : null;

        return $this;
    }

    public function getHeroImage(): ?string
    {
        return $this->heroImage;
    }

    public function setHeroImage(?string $heroImage): self
    {
        $this->heroImage = $heroImage ? trim($heroImage) : null;

        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): self
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        if ($isPublished && $this->publishedAt === null) {
            $this->publishedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getRelatedCategory(): ?Category
    {
        return $this->relatedCategory;
    }

    public function setRelatedCategory(?Category $relatedCategory): self
    {
        $this->relatedCategory = $relatedCategory;

        return $this;
    }

    public function getRelatedBrandSlug(): ?string
    {
        return $this->relatedBrandSlug;
    }

    public function setRelatedBrandSlug(?string $relatedBrandSlug): self
    {
        $this->relatedBrandSlug = $relatedBrandSlug
            ? trim($relatedBrandSlug)
            : null;

        return $this;
    }

    /**
     * @return Collection<int, MagazineFaq>
     */
    public function getFaqs(): Collection
    {
        return $this->faqs;
    }

    public function addFaq(MagazineFaq $faq): self
    {
        if (!$this->faqs->contains($faq)) {
            $this->faqs->add($faq);
            $faq->setArticle($this);
        }

        return $this;
    }

    public function removeFaq(MagazineFaq $faq): self
    {
        if ($this->faqs->removeElement($faq)) {
            if ($faq->getArticle() === $this) {
                $faq->setArticle(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Brand>
     */
    public function getRelatedBrands(): Collection
    {
        return $this->relatedBrands;
    }

    public function addRelatedBrand(Brand $brand): self
    {
        if (!$this->relatedBrands->contains($brand)) {
            $this->relatedBrands->add($brand);
        }

        return $this;
    }

    public function removeRelatedBrand(Brand $brand): self
    {
        $this->relatedBrands->removeElement($brand);

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getRelatedProducts(): Collection
    {
        return $this->relatedProducts;
    }

    public function addRelatedProduct(Product $product): self
    {
        if (!$this->relatedProducts->contains($product)) {
            $this->relatedProducts->add($product);
        }

        return $this;
    }

    public function removeRelatedProduct(Product $product): self
    {
        $this->relatedProducts->removeElement($product);

        return $this;
    }

    public function getReadingTime(): int
    {
        $text = html_entity_decode(
            strip_tags($this->content),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $text = trim($text);

        if ($text === '') {
            return 1;
        }

        preg_match_all('/[\p{L}\p{N}\']+/u', $text, $matches);

        $wordCount = count($matches[0]);

        return max(1, (int) ceil($wordCount / 200));
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTimeImmutable();

        if ($this->isPublished && $this->publishedAt === null) {
            $this->publishedAt = new \DateTimeImmutable();
        }
    }
}