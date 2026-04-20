<?php

namespace App\Shop\Entity;

use App\Account\Entity\CustomerUser;
use App\Shop\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'shop_order')]
class Order
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    public const SHIPPING_METHOD_HOME = 'home';
    public const SHIPPING_METHOD_PICKUP = 'pickup';
    public const SHIPPING_METHOD_STORE_PICKUP = 'store_pickup';

    private const ALLOWED_STATUSES = [
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_PAID,
        self::STATUS_CANCELLED,
        self::STATUS_SHIPPED,
        self::STATUS_FAILED,
        self::STATUS_EXPIRED,
    ];

    private const ALLOWED_SHIPPING_METHODS = [
        self::SHIPPING_METHOD_HOME,
        self::SHIPPING_METHOD_PICKUP,
        self::SHIPPING_METHOD_STORE_PICKUP,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    private string $orderNumber;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_PENDING_PAYMENT;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $subtotal = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $shippingCost = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $total = '0.00';

    #[ORM\Column(length: 255)]
    private string $customerEmail;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $customerPhone = null;

    #[ORM\ManyToOne(targetEntity: CustomerUser::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CustomerUser $customerUser = null;

    #[ORM\Column(type: 'json')]
    private array $shippingAddress = [];

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $molliePaymentId = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 20, options: ['default' => self::SHIPPING_METHOD_HOME])]
    private string $shippingMethod = self::SHIPPING_METHOD_HOME;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $pickupLocationCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $pickupRetailNetworkId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pickupPointName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pickupStreet = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $pickupHouseNumber = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $pickupPostalCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $pickupCity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $storePickupName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $storePickupStreet = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $storePickupPostalCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $storePickupCity = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $storePickupCountry = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $couponCode = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $discountAmount = '0.00';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $trackingCode = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $trackingUrl = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $shippedAt = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(
        mappedBy: 'order',
        targetEntity: OrderItem::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): self
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new \InvalidArgumentException('Invalid order status.');
        }

        $this->status = $status;

        return $this;
    }

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function setSubtotal(string|float|int $subtotal): self
    {
        $this->subtotal = number_format((float) $subtotal, 2, '.', '');
        $this->calculateTotal();

        return $this;
    }

    public function getShippingCost(): string
    {
        return $this->shippingCost;
    }

    public function setShippingCost(string|float|int $shippingCost): self
    {
        $this->shippingCost = number_format((float) $shippingCost, 2, '.', '');
        $this->calculateTotal();

        return $this;
    }

    public function getTotal(): string
    {
        return $this->total;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $email): self
    {
        $this->customerEmail = $email;

        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(?string $phone): self
    {
        $this->customerPhone = $phone;

        return $this;
    }

    public function getCustomerUser(): ?CustomerUser
    {
        return $this->customerUser;
    }

    public function setCustomerUser(?CustomerUser $customerUser): self
    {
        $this->customerUser = $customerUser;

        return $this;
    }

    public function getShippingAddress(): array
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(array $address): self
    {
        $this->shippingAddress = $address;

        return $this;
    }

    public function getMolliePaymentId(): ?string
    {
        return $this->molliePaymentId;
    }

    public function setMolliePaymentId(?string $id): self
    {
        $this->molliePaymentId = $id;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getShippingMethod(): string
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(string $shippingMethod): self
    {
        if (!in_array($shippingMethod, self::ALLOWED_SHIPPING_METHODS, true)) {
            throw new \InvalidArgumentException('Invalid shipping method.');
        }

        $this->shippingMethod = $shippingMethod;

        if ($shippingMethod === self::SHIPPING_METHOD_HOME) {
            $this->clearPickupData();
            $this->clearStorePickupData();
        }

        if ($shippingMethod === self::SHIPPING_METHOD_PICKUP) {
            $this->clearStorePickupData();
        }

        if ($shippingMethod === self::SHIPPING_METHOD_STORE_PICKUP) {
            $this->clearPickupData();
        }

        return $this;
    }

    public function getPickupLocationCode(): ?string
    {
        return $this->pickupLocationCode;
    }

    public function setPickupLocationCode(?string $pickupLocationCode): self
    {
        $this->pickupLocationCode = $pickupLocationCode;

        return $this;
    }

    public function getPickupRetailNetworkId(): ?string
    {
        return $this->pickupRetailNetworkId;
    }

    public function setPickupRetailNetworkId(?string $pickupRetailNetworkId): self
    {
        $this->pickupRetailNetworkId = $pickupRetailNetworkId;

        return $this;
    }

    public function getPickupPointName(): ?string
    {
        return $this->pickupPointName;
    }

    public function setPickupPointName(?string $pickupPointName): self
    {
        $this->pickupPointName = $pickupPointName;

        return $this;
    }

    public function getPickupStreet(): ?string
    {
        return $this->pickupStreet;
    }

    public function setPickupStreet(?string $pickupStreet): self
    {
        $this->pickupStreet = $pickupStreet;

        return $this;
    }

    public function getPickupHouseNumber(): ?string
    {
        return $this->pickupHouseNumber;
    }

    public function setPickupHouseNumber(?string $pickupHouseNumber): self
    {
        $this->pickupHouseNumber = $pickupHouseNumber;

        return $this;
    }

    public function getPickupPostalCode(): ?string
    {
        return $this->pickupPostalCode;
    }

    public function setPickupPostalCode(?string $pickupPostalCode): self
    {
        $this->pickupPostalCode = $pickupPostalCode;

        return $this;
    }

    public function getPickupCity(): ?string
    {
        return $this->pickupCity;
    }

    public function setPickupCity(?string $pickupCity): self
    {
        $this->pickupCity = $pickupCity;

        return $this;
    }

    public function getStorePickupName(): ?string
    {
        return $this->storePickupName;
    }

    public function setStorePickupName(?string $storePickupName): self
    {
        $this->storePickupName = $storePickupName !== null ? trim($storePickupName) : null;

        return $this;
    }

    public function getStorePickupStreet(): ?string
    {
        return $this->storePickupStreet;
    }

    public function setStorePickupStreet(?string $storePickupStreet): self
    {
        $this->storePickupStreet = $storePickupStreet !== null ? trim($storePickupStreet) : null;

        return $this;
    }

    public function getStorePickupPostalCode(): ?string
    {
        return $this->storePickupPostalCode;
    }

    public function setStorePickupPostalCode(?string $storePickupPostalCode): self
    {
        $this->storePickupPostalCode = $storePickupPostalCode !== null
            ? strtoupper(trim($storePickupPostalCode))
            : null;

        return $this;
    }

    public function getStorePickupCity(): ?string
    {
        return $this->storePickupCity;
    }

    public function setStorePickupCity(?string $storePickupCity): self
    {
        $this->storePickupCity = $storePickupCity !== null ? trim($storePickupCity) : null;

        return $this;
    }

    public function getStorePickupCountry(): ?string
    {
        return $this->storePickupCountry;
    }

    public function setStorePickupCountry(?string $storePickupCountry): self
    {
        $this->storePickupCountry = $storePickupCountry !== null
            ? strtoupper(trim($storePickupCountry))
            : null;

        return $this;
    }

    public function getCouponCode(): ?string
    {
        return $this->couponCode;
    }

    public function setCouponCode(?string $couponCode): self
    {
        $this->couponCode = $couponCode !== null
            ? mb_strtoupper(trim($couponCode))
            : null;

        return $this;
    }

    public function getDiscountAmount(): string
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(string|float|int $discountAmount): self
    {
        $this->discountAmount = number_format((float) $discountAmount, 2, '.', '');
        $this->calculateTotal();

        return $this;
    }

    public function markAsPaid(): void
    {
        $this->setStatus(self::STATUS_PAID);
    }

    public function markAsCancelled(): void
    {
        $this->setStatus(self::STATUS_CANCELLED);
    }

    public function getTrackingCode(): ?string
    {
        return $this->trackingCode;
    }

    public function setTrackingCode(?string $trackingCode): self
    {
        $this->trackingCode = $trackingCode !== null
            ? trim($trackingCode)
            : null;

        return $this;
    }

    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    public function setTrackingUrl(?string $trackingUrl): self
    {
        $this->trackingUrl = $trackingUrl !== null
            ? trim($trackingUrl)
            : null;

        return $this;
    }

    public function getShippedAt(): ?\DateTimeImmutable
    {
        return $this->shippedAt;
    }

    public function setShippedAt(?\DateTimeImmutable $shippedAt): self
    {
        $this->shippedAt = $shippedAt;

        return $this;
    }

    public function markAsShipped(): void
    {
        $this->setStatus(self::STATUS_SHIPPED);

        if ($this->shippedAt === null) {
            $this->shippedAt = new \DateTimeImmutable();
        }
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isShipped(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    public function isPickupOrder(): bool
    {
        return $this->shippingMethod === self::SHIPPING_METHOD_PICKUP;
    }

    public function isStorePickup(): bool
    {
        return $this->shippingMethod === self::SHIPPING_METHOD_STORE_PICKUP;
    }

    public function isHomeDelivery(): bool
    {
        return $this->shippingMethod === self::SHIPPING_METHOD_HOME;
    }

    public function clearPickupData(): self
    {
        $this->pickupLocationCode = null;
        $this->pickupRetailNetworkId = null;
        $this->pickupPointName = null;
        $this->pickupStreet = null;
        $this->pickupHouseNumber = null;
        $this->pickupPostalCode = null;
        $this->pickupCity = null;

        return $this;
    }

    public function clearStorePickupData(): self
    {
        $this->storePickupName = null;
        $this->storePickupStreet = null;
        $this->storePickupPostalCode = null;
        $this->storePickupCity = null;
        $this->storePickupCountry = null;

        return $this;
    }

    private function calculateTotal(): void
    {
        $total = (float) $this->subtotal
            - (float) $this->discountAmount
            + (float) $this->shippingCost;

        $this->total = number_format(max(0, $total), 2, '.', '');
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): self
    {
        if ($this->items->removeElement($item)) {
            $item->setOrder(null);
        }

        return $this;
    }

    public function getOrderItemsPreview(): string
    {
        $lines = [];

        foreach ($this->items as $item) {
            $lines[] = sprintf(
                '%s × %d — € %s',
                $item->getProductName(),
                $item->getQty(),
                number_format((float) $item->getLineTotal(), 2, ',', '.')
            );
        }

        return implode("\n", $lines);
    }
}