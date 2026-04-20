<?php

namespace App\Shop\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private const CART_KEY = 'cart.items';
    private const COUPON_KEY = 'cart.coupon_code';

    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    private function session(): SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        $session = $request?->getSession();

        if (!$session instanceof SessionInterface) {
            throw new \RuntimeException('No active session available.');
        }

        return $session;
    }

    /**
     * @return array<int, array{sku: string, qty: int}>
     */
    public function all(): array
    {
        return $this->session()->get(self::CART_KEY, []);
    }

    public function add(string $sku, int $qty = 1): void
    {
        $items = $this->all();

        foreach ($items as &$item) {
            if (($item['sku'] ?? '') === $sku) {
                $item['qty'] = max(1, (int) ($item['qty'] ?? 0) + $qty);
                $this->session()->set(self::CART_KEY, $items);

                return;
            }
        }

        $items[] = [
            'sku' => $sku,
            'qty' => max(1, $qty),
        ];

        $this->session()->set(self::CART_KEY, $items);
    }

    public function setQty(string $sku, int $qty): void
    {
        $items = $this->all();

        foreach ($items as $index => $item) {
            if (($item['sku'] ?? '') !== $sku) {
                continue;
            }

            if ($qty <= 0) {
                unset($items[$index]);
            } else {
                $items[$index]['qty'] = max(1, $qty);
            }

            $this->session()->set(self::CART_KEY, array_values($items));

            return;
        }
    }

    public function remove(string $sku): void
    {
        $items = array_values(array_filter(
            $this->all(),
            static fn (array $item): bool => ($item['sku'] ?? '') !== $sku
        ));

        $this->session()->set(self::CART_KEY, $items);
    }

    public function clear(): void
    {
        $this->session()->remove(self::CART_KEY);
        $this->clearCouponCode();
    }

    public function countItems(): int
    {
        $count = 0;

        foreach ($this->all() as $item) {
            $count += max(0, (int) ($item['qty'] ?? 0));
        }

        return $count;
    }

    public function setCouponCode(string $code): void
    {
        $this->session()->set(self::COUPON_KEY, mb_strtoupper(trim($code)));
    }

    public function getCouponCode(): ?string
    {
        $code = $this->session()->get(self::COUPON_KEY);

        if (!is_string($code) || trim($code) === '') {
            return null;
        }

        return mb_strtoupper(trim($code));
    }

    public function hasCouponCode(): bool
    {
        return $this->getCouponCode() !== null;
    }

    public function clearCouponCode(): void
    {
        $this->session()->remove(self::COUPON_KEY);
    }
}