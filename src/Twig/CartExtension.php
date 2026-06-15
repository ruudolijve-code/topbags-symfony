<?php

declare(strict_types=1);

namespace App\Twig;

use App\Shop\Service\CartService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class CartExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly CartService $cartService,
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'cartCount' => $this->cartService->countItems(),
        ];
    }
}
