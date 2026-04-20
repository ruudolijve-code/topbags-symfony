<?php

namespace App\Shop\Controller;

use App\Account\Entity\CustomerUser;
use App\Catalog\Repository\ProductVariantRepository;
use App\Catalog\Service\AvailabilityService;
use App\Shop\Entity\Order;
use App\Shop\Service\CartService;
use App\Shop\Service\Coupon\CouponService;
use App\Shop\Service\MollieService;
use App\Shop\Service\OrderService;
use App\Shop\Service\PostNLService;
use App\Shop\Service\ShippingCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'checkout_index', methods: ['GET'])]
    public function index(
        CartService $cart,
        ProductVariantRepository $variantRepository,
        ShippingCalculator $shippingCalculator,
        CouponService $couponService
    ): Response {
        $cartData = $this->buildCartView($cart, $variantRepository);

        if ($cartData['items'] === []) {
            return $this->redirectToRoute('cart_show');
        }

        return $this->renderCheckout(
            cartData: $cartData,
            formData: $this->emptyFormData(),
            cart: $cart,
            shippingCalculator: $shippingCalculator,
            couponService: $couponService
        );
    }

    #[Route('/checkout/pickup-points', name: 'checkout_pickup_points', methods: ['GET'])]
    public function pickupPoints(
        Request $request,
        PostNLService $postNLService
    ): JsonResponse {
        $postalCode = strtoupper(trim((string) $request->query->get('postalCode', '')));
        $houseNumber = trim((string) $request->query->get('houseNumber', ''));

        if ($postalCode === '' || $houseNumber === '') {
            return new JsonResponse([
                'ok' => false,
                'error' => 'Postcode of huisnummer ontbreekt',
                'locations' => [],
            ], 400);
        }

        $result = $postNLService->getPickupPoints($postalCode, $houseNumber);

        if (($result['success'] ?? false) !== true) {
            return new JsonResponse([
                'ok' => false,
                'error' => $result['error'] ?? 'UNKNOWN_ERROR',
                'details' => $result['details'] ?? ($result['raw'] ?? null),
                'locations' => [],
            ], 400);
        }

        return new JsonResponse([
            'ok' => true,
            'locations' => $result['results'],
        ]);
    }

    #[Route('/checkout/confirm', name: 'checkout_confirm', methods: ['POST'])]
    public function confirm(
        Request $request,
        CartService $cart,
        ProductVariantRepository $variantRepository,
        AvailabilityService $availabilityService,
        OrderService $orderService,
        MollieService $mollie,
        ParameterBagInterface $params,
        ShippingCalculator $shippingCalculator,
        CouponService $couponService,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $cartData = $this->buildCartView($cart, $variantRepository);

        if ($cartData['items'] === []) {
            $this->addFlash('error', 'Je winkelwagen is leeg.');

            return $this->redirectToRoute('cart_show');
        }

        $customerData = $this->buildCustomerData($request);

        $availabilityErrors = $this->validateCartAvailability(
            $cartData['items'],
            $variantRepository,
            $availabilityService
        );

        if ($availabilityErrors !== []) {
            foreach ($availabilityErrors as $message) {
                $this->addFlash('error', $message);
            }

            return $this->renderCheckout(
                cartData: $cartData,
                formData: $customerData,
                cart: $cart,
                shippingCalculator: $shippingCalculator,
                couponService: $couponService
            );
        }

        $customerErrors = $this->validateCustomerData($customerData);

        if ($customerErrors !== []) {
            foreach ($customerErrors as $message) {
                $this->addFlash('error', $message);
            }

            return $this->renderCheckout(
                cartData: $cartData,
                formData: $customerData,
                cart: $cart,
                shippingCalculator: $shippingCalculator,
                couponService: $couponService
            );
        }

        $accountErrors = $this->validateAccountData($customerData, $em);

        if ($accountErrors !== []) {
            foreach ($accountErrors as $message) {
                $this->addFlash('error', $message);
            }

            return $this->renderCheckout(
                cartData: $cartData,
                formData: $customerData,
                cart: $cart,
                shippingCalculator: $shippingCalculator,
                couponService: $couponService
            );
        }

        $couponCode = $cart->getCouponCode();
        $discountAmount = 0.0;

        if ($couponCode !== null) {
            $couponResult = $couponService->validate($couponCode, $cartData['subtotal']);

            if (!$couponResult->isValid()) {
                $cart->clearCouponCode();
                $this->addFlash('error', $couponResult->getMessage() ?? 'De couponcode is niet meer geldig.');

                return $this->renderCheckout(
                    cartData: $cartData,
                    formData: $customerData,
                    cart: $cart,
                    shippingCalculator: $shippingCalculator,
                    couponService: $couponService
                );
            }

            $discountAmount = $couponResult->getDiscountAmount();
        }

        $customerUser = $this->getUser();

        if (!$customerUser instanceof CustomerUser) {
            $customerUser = null;
        }

        if ($customerUser === null && $customerData['createAccount']) {
            $customerUser = $this->createCustomerUser(
                customerData: $customerData,
                em: $em,
                passwordHasher: $passwordHasher
            );
        }

        $shippingMethod = $customerData['shipping']['method'] ?? Order::SHIPPING_METHOD_HOME;

        $shippingCost = $shippingCalculator->calculate(
            $cartData['subtotal'],
            $shippingMethod
        );

        $order = $orderService->createOrder(
            cartItems: $cartData['items'],
            customerData: $customerData,
            shippingCost: $shippingCost,
            discountAmount: $discountAmount,
            couponCode: $couponCode,
            customerUser: $customerUser
        );

        $appUrl = rtrim((string) $params->get('app.url'), '/');
        $redirectUrl = $appUrl . '/order/' . $order->getOrderNumber();
        $webhookUrl = $appUrl . '/payment/webhook';

        $payment = $mollie->createPayment(
            total: (float) $order->getTotal(),
            orderNumber: $order->getOrderNumber(),
            metadata: ['order_id' => $order->getId()],
            redirectUrl: $redirectUrl,
            webhookUrl: $webhookUrl
        );

        $orderService->attachMolliePaymentId($order, $payment->id);

        return $this->redirect($payment->getCheckoutUrl(), 303);
    }

    /**
     * @return array{
     *   items: array<int, array{
     *     sku: string,
     *     name: string,
     *     price: float,
     *     qty: int,
     *     lineTotal: float,
     *     color?: string,
     *     regularPrice?: float,
     *     compareAtPrice?: ?float,
     *     saleActive?: bool,
     *     saleBadge?: ?string
     *   }>,
     *   subtotal: float
     * }
     */
    private function buildCartView(
        CartService $cart,
        ProductVariantRepository $variantRepository
    ): array {
        $rawItems = $cart->all();
        $items = [];
        $subtotal = 0.0;

        foreach ($rawItems as $row) {
            if (!isset($row['sku'], $row['qty'])) {
                continue;
            }

            $variant = $variantRepository->findOneBy([
                'variantSku' => $row['sku'],
                'isActive' => true,
            ]);

            if ($variant === null) {
                continue;
            }

            $price = (float) $variant->getDisplayPrice();
            $qty = max(1, (int) $row['qty']);
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;

            $items[] = [
                'sku' => $variant->getVariantSku(),
                'name' => $variant->getProduct()->getName(),
                'price' => $price,
                'qty' => $qty,
                'lineTotal' => $lineTotal,
                'color' => $variant->getSupplierColorName(),
                'regularPrice' => (float) $variant->getPrice(),
                'compareAtPrice' => $variant->getCompareAtPrice() !== null
                    ? (float) $variant->getCompareAtPrice()
                    : null,
                'saleActive' => $variant->isSaleActive(),
                'saleBadge' => $variant->getDiscountBadge(),
            ];
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
        ];
    }

    /**
     * @param array<int, array{
     *   sku: string,
     *   name: string,
     *   price: float,
     *   qty: int,
     *   lineTotal: float
     * }> $cartItems
     *
     * @return string[]
     */
    private function validateCartAvailability(
        array $cartItems,
        ProductVariantRepository $variantRepository,
        AvailabilityService $availabilityService
    ): array {
        $errors = [];

        foreach ($cartItems as $item) {
            $variant = $variantRepository->findOneBy([
                'variantSku' => $item['sku'],
                'isActive' => true,
            ]);

            if ($variant === null) {
                $errors[] = sprintf(
                    'Product "%s" is niet meer beschikbaar.',
                    $item['name']
                );
                continue;
            }

            $availability = $availabilityService->get($variant);

            if ($availability->isOutOfStock()) {
                $errors[] = sprintf(
                    'Product "%s" is momenteel niet leverbaar en kan niet worden besteld.',
                    $item['name']
                );
                continue;
            }

            if (
                !$variant->allowsBackorder()
                && $item['qty'] > $variant->getAvailableStock()
            ) {
                $errors[] = sprintf(
                    'Van "%s" zijn nog maar %d stuk(s) beschikbaar.',
                    $item['name'],
                    $variant->getAvailableStock()
                );
            }
        }

        return $errors;
    }

    /**
     * @return array{
     *   email: string,
     *   rawPhone: string,
     *   phone: ?string,
     *   createAccount: bool,
     *   accountPassword: string,
     *   address: array{
     *     firstName: string,
     *     lastName: string,
     *     street: string,
     *     postalCode: string,
     *     city: string,
     *     country: string
     *   },
     *   shipping: array{
     *     method: string,
     *     pickup: array{
     *       locationCode: string,
     *       retailNetworkId: string,
     *       name: string,
     *       street: string,
     *       houseNumber: string,
     *       postalCode: string,
     *       city: string
     *     },
     *     storePickup: array{
     *       name: string,
     *       street: string,
     *       postalCode: string,
     *       city: string,
     *       country: string
     *     }
     *   }
     * }
     */
    private function buildCustomerData(Request $request): array
    {
        $rawPhone = trim((string) $request->request->get('phone', ''));
        $phone = $this->normalizeNlPhone($rawPhone);

        return [
            'email' => mb_strtolower(trim((string) $request->request->get('email', ''))),
            'rawPhone' => $rawPhone,
            'phone' => $phone,
            'createAccount' => $request->request->getBoolean('createAccount'),
            'accountPassword' => trim((string) $request->request->get('accountPassword', '')),
            'address' => [
                'firstName' => trim((string) $request->request->get('firstName', '')),
                'lastName' => trim((string) $request->request->get('lastName', '')),
                'street' => trim((string) $request->request->get('street', '')),
                'postalCode' => strtoupper(trim((string) $request->request->get('postalCode', ''))),
                'city' => trim((string) $request->request->get('city', '')),
                'country' => strtoupper(trim((string) $request->request->get('country', 'NL'))) ?: 'NL',
            ],
            'shipping' => [
                'method' => trim((string) $request->request->get('shippingMethod', 'home')),
                'pickup' => [
                    'locationCode' => trim((string) $request->request->get('pickupLocationCode', '')),
                    'retailNetworkId' => trim((string) $request->request->get('pickupRetailNetworkId', '')),
                    'name' => trim((string) $request->request->get('pickupName', '')),
                    'street' => trim((string) $request->request->get('pickupStreet', '')),
                    'houseNumber' => trim((string) $request->request->get('pickupHouseNumber', '')),
                    'postalCode' => strtoupper(trim((string) $request->request->get('pickupPostalCode', ''))),
                    'city' => trim((string) $request->request->get('pickupCity', '')),
                ],
                'storePickup' => [],
            ],
        ];
    }

    /**
     * @return array{
     *   email: string,
     *   rawPhone: string,
     *   phone: ?string,
     *   createAccount: bool,
     *   accountPassword: string,
     *   address: array{
     *     firstName: string,
     *     lastName: string,
     *     street: string,
     *     postalCode: string,
     *     city: string,
     *     country: string
     *   },
     *   shipping: array{
     *     method: string,
     *     pickup: array{
     *       locationCode: string,
     *       retailNetworkId: string,
     *       name: string,
     *       street: string,
     *       houseNumber: string,
     *       postalCode: string,
     *       city: string
     *     },
     *     storePickup: array
     *   }
     * }
     */
    private function emptyFormData(): array
    {
        return [
            'email' => '',
            'rawPhone' => '',
            'phone' => null,
            'createAccount' => false,
            'accountPassword' => '',
            'address' => [
                'firstName' => '',
                'lastName' => '',
                'street' => '',
                'postalCode' => '',
                'city' => '',
                'country' => 'NL',
            ],
            'shipping' => [
                'method' => 'home',
                'pickup' => [
                    'locationCode' => '',
                    'retailNetworkId' => '',
                    'name' => '',
                    'street' => '',
                    'houseNumber' => '',
                    'postalCode' => '',
                    'city' => '',
                ],
                'storePickup' => [],
            ],
        ];
    }

    /**
     * @param array{
     *   email: string,
     *   rawPhone: string,
     *   phone: ?string,
     *   createAccount: bool,
     *   accountPassword: string,
     *   address: array{
     *     firstName: string,
     *     lastName: string,
     *     street: string,
     *     postalCode: string,
     *     city: string,
     *     country: string
     *   },
     *   shipping: array{
     *     method: string,
     *     pickup: array{
     *       locationCode: string,
     *       retailNetworkId: string,
     *       name: string,
     *       street: string,
     *       houseNumber: string,
     *       postalCode: string,
     *       city: string
     *     },
     *     storePickup: array{
     *       name: string,
     *       street: string,
     *       postalCode: string,
     *       city: string,
     *       country: string
     *     }
     *   }
     * } $customerData
     *
     * @return string[]
     */
    private function validateCustomerData(array $customerData): array
    {
        $errors = [];

        if ($customerData['address']['firstName'] === '') {
            $errors[] = 'Vul je voornaam in.';
        }

        if ($customerData['address']['lastName'] === '') {
            $errors[] = 'Vul je achternaam in.';
        }

        if ($customerData['address']['street'] === '') {
            $errors[] = 'Vul je straat en huisnummer in.';
        }

        if ($customerData['address']['city'] === '') {
            $errors[] = 'Vul je woonplaats in.';
        }

        if (!$this->isValidEmail($customerData['email'])) {
            $errors[] = 'Vul een geldig e-mailadres in.';
        }

        if (!$this->isValidNlPostalCode($customerData['address']['postalCode'])) {
            $errors[] = 'Vul een geldige Nederlandse postcode in, bijvoorbeeld 7551 AB.';
        }

        if ($customerData['rawPhone'] === '') {
            $errors[] = 'Vul je telefoonnummer in.';
        } elseif ($this->containsLetters($customerData['rawPhone'])) {
            $errors[] = 'Gebruik alleen cijfers en gewone tekens zoals spaties of een streepje in je telefoonnummer.';
        } elseif ($customerData['phone'] === null || !$this->isValidNlPhone($customerData['phone'])) {
            $errors[] = 'Vul een geldig telefoonnummer in, bijvoorbeeld 06 12345678.';
        }

        $shippingMethod = $customerData['shipping']['method'];

        if (!in_array($shippingMethod, ['home', 'pickup', 'store_pickup'], true)) {
            $errors[] = 'Kies een geldige verzendmethode.';
        }

        if ($shippingMethod === 'pickup') {
            $pickup = $customerData['shipping']['pickup'];

            if ($pickup['locationCode'] === '') {
                $errors[] = 'Kies een PostNL afhaalpunt.';
            }

            if ($pickup['name'] === '') {
                $errors[] = 'Het gekozen afhaalpunt is niet compleet.';
            }

            if ($pickup['postalCode'] !== '' && !$this->isValidNlPostalCode($pickup['postalCode'])) {
                $errors[] = 'Het gekozen afhaalpunt heeft geen geldige postcode.';
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param array{
     *   email: string,
     *   rawPhone: string,
     *   phone: ?string,
     *   createAccount: bool,
     *   accountPassword: string,
     *   address: array{
     *     firstName: string,
     *     lastName: string,
     *     street: string,
     *     postalCode: string,
     *     city: string,
     *     country: string
     *   },
     *   shipping: array{
     *     method: string,
     *     pickup: array{
     *       locationCode: string,
     *       retailNetworkId: string,
     *       name: string,
     *       street: string,
     *       houseNumber: string,
     *       postalCode: string,
     *       city: string
     *     },
     *     storePickup: array{
     *       name: string,
     *       street: string,
     *       postalCode: string,
     *       city: string,
     *       country: string
     *     }
     *   }
     * } $customerData
     *
     * @return string[]
     */
    private function validateAccountData(
        array $customerData,
        EntityManagerInterface $em
    ): array {
        $errors = [];

        if (!$customerData['createAccount']) {
            return [];
        }

        if (mb_strlen($customerData['accountPassword']) < 6) {
            $errors[] = 'Kies een wachtwoord van minimaal 6 tekens.';
        }

        $existingUser = $em->getRepository(CustomerUser::class)->findOneBy([
            'email' => $customerData['email'],
        ]);

        if ($existingUser !== null) {
            $errors[] = 'Er bestaat al een account met dit e-mailadres. Log in of reken af als gast.';
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param array{
     *   email: string,
     *   rawPhone: string,
     *   phone: ?string,
     *   createAccount: bool,
     *   accountPassword: string,
     *   address: array{
     *     firstName: string,
     *     lastName: string,
     *     street: string,
     *     postalCode: string,
     *     city: string,
     *     country: string
     *   },
     *   shipping: array{
     *     method: string,
     *     pickup: array{
     *       locationCode: string,
     *       retailNetworkId: string,
     *       name: string,
     *       street: string,
     *       houseNumber: string,
     *       postalCode: string,
     *       city: string
     *     },
     *     storePickup: array{
     *       name: string,
     *       street: string,
     *       postalCode: string,
     *       city: string,
     *       country: string
     *     }
     *   }
     * } $customerData
     */
    private function createCustomerUser(
        array $customerData,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): CustomerUser {
        $user = new CustomerUser();
        $user
            ->setEmail($customerData['email'])
            ->setFirstName($customerData['address']['firstName'])
            ->setLastName($customerData['address']['lastName'])
            ->setPhone($customerData['phone']);

        $user->setPassword(
            $passwordHasher->hashPassword($user, $customerData['accountPassword'])
        );

        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * @param array{
     *   items: array<int, array{
     *     sku: string,
     *     name: string,
     *     price: float,
     *     qty: int,
     *     lineTotal: float,
     *     color?: string,
     *     regularPrice?: float,
     *     compareAtPrice?: ?float,
     *     saleActive?: bool,
     *     saleBadge?: ?string
     *   }>,
     *   subtotal: float
     * } $cartData
     * @param array{
     *   email: string,
     *   rawPhone: string,
     *   phone: ?string,
     *   createAccount: bool,
     *   accountPassword: string,
     *   address: array{
     *     firstName: string,
     *     lastName: string,
     *     street: string,
     *     postalCode: string,
     *     city: string,
     *     country: string
     *   },
     *   shipping: array{
     *     method: string,
     *     pickup: array{
     *       locationCode: string,
     *       retailNetworkId: string,
     *       name: string,
     *       street: string,
     *       houseNumber: string,
     *       postalCode: string,
     *       city: string
     *     },
     *     storePickup: array{
     *       name: string,
     *       street: string,
     *       postalCode: string,
     *       city: string,
     *       country: string
     *     }
     *   }
     * } $formData
     */
    private function renderCheckout(
        array $cartData,
        array $formData,
        CartService $cart,
        ShippingCalculator $shippingCalculator,
        CouponService $couponService
    ): Response {
        $subtotal = $cartData['subtotal'];
        $couponCode = $cart->getCouponCode();
        $couponResult = null;
        $discountAmount = 0.0;

        if ($couponCode !== null) {
            $couponResult = $couponService->validate($couponCode, $subtotal);

            if (!$couponResult->isValid()) {
                $cart->clearCouponCode();
                $couponCode = null;
                $couponResult = null;
            } else {
                $discountAmount = $couponResult->getDiscountAmount();
            }
        }

        $shippingMethod = $formData['shipping']['method'] ?? Order::SHIPPING_METHOD_HOME;

        $shippingCost = $shippingCalculator->calculate(
            $subtotal,
            $shippingMethod
        );
        $grandTotal = max(0, $subtotal - $discountAmount + $shippingCost);

        return $this->render('checkout/index.html.twig', [
            'items' => $cartData['items'],
            'subtotal' => $subtotal,
            'formData' => $formData,
            'couponCode' => $couponCode,
            'couponResult' => $couponResult,
            'discountAmount' => $discountAmount,
            'shippingCost' => $shippingCost,
            'freeShippingFrom' => $shippingCalculator->getFreeFrom(),
            'grandTotal' => $grandTotal,
        ]);
    }

    private function normalizeNlPhone(string $input): ?string
    {
        $value = trim($input);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^\d+]/', '', $value) ?? '';

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '+31')) {
            $value = '0' . substr($value, 3);
        } elseif (str_starts_with($value, '31')) {
            $value = '0' . substr($value, 2);
        }

        $value = preg_replace('/\D/', '', $value) ?? '';

        return $value !== '' ? $value : null;
    }

    private function containsLetters(string $value): bool
    {
        return (bool) preg_match('/[a-zA-Z]/u', $value);
    }

    private function isValidNlPhone(string $phone): bool
    {
        return (bool) preg_match('/^0[1-9]\d{8,9}$/', $phone);
    }

    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function isValidNlPostalCode(string $postalCode): bool
    {
        return (bool) preg_match('/^[1-9][0-9]{3}\s?[A-Z]{2}$/', $postalCode);
    }
}