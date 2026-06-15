<?php

declare(strict_types=1);

namespace App\Shop\Service;

use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final readonly class CartService
{
    private const CART_KEY = 'cart.items';
    private const COUPON_KEY = 'cart.coupon_code';

    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    /**
     * Geeft de actieve sessie terug wanneer die beschikbaar is.
     *
     * Bij CLI-processen, Messenger-workers en e-mailrendering
     * bestaat meestal geen request of sessie.
     */
    private function optionalSession(): ?SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null || !$request->hasSession()) {
            return null;
        }

        return $request->getSession();
    }

    /**
     * Schrijfacties vereisen altijd een actieve sessie.
     */
    private function requiredSession(): SessionInterface
    {
        $session = $this->optionalSession();

        if (!$session instanceof SessionInterface) {
            throw new RuntimeException('No active session available.');
        }

        return $session;
    }

    /**
     * @return array<int, array{sku: string, qty: int}>
     */
    public function all(): array
    {
        $session = $this->optionalSession();

        if (!$session instanceof SessionInterface) {
            return [];
        }

        $items = $session->get(self::CART_KEY, []);

        return is_array($items) ? $items : [];
    }

    public function add(string $sku, int $qty = 1): void
    {
        $session = $this->requiredSession();
        $items = $this->all();

        foreach ($items as &$item) {
            if (($item['sku'] ?? '') !== $sku) {
                continue;
            }

            $item['qty'] = max(
                1,
                (int) ($item['qty'] ?? 0) + $qty
            );

            $session->set(self::CART_KEY, $items);

            return;
        }

        unset($item);

        $items[] = [
            'sku' => $sku,
            'qty' => max(1, $qty),
        ];

        $session->set(self::CART_KEY, $items);
    }

    public function setQty(string $sku, int $qty): void
    {
        $session = $this->requiredSession();
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

            $session->set(
                self::CART_KEY,
                array_values($items)
            );

            return;
        }
    }

    public function remove(string $sku): void
    {
        $session = $this->requiredSession();

        $items = array_values(
            array_filter(
                $this->all(),
                static fn (array $item): bool =>
                    ($item['sku'] ?? '') !== $sku
            )
        );

        $session->set(self::CART_KEY, $items);
    }

    public function clear(): void
    {
        $session = $this->requiredSession();

        $session->remove(self::CART_KEY);
        $session->remove(self::COUPON_KEY);
    }

    public function countItems(): int
    {
        $count = 0;

        foreach ($this->all() as $item) {
            $count += max(
                0,
                (int) ($item['qty'] ?? 0)
            );
        }

        return $count;
    }

    public function setCouponCode(string $code): void
    {
        $this->requiredSession()->set(
            self::COUPON_KEY,
            mb_strtoupper(trim($code))
        );
    }

    public function getCouponCode(): ?string
    {
        $session = $this->optionalSession();

        if (!$session instanceof SessionInterface) {
            return null;
        }

        $code = $session->get(self::COUPON_KEY);

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
        $this->requiredSession()->remove(self::COUPON_KEY);
    }
}
