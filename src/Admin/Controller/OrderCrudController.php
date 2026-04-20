<?php

namespace App\Admin\Controller;

use App\Shop\Entity\Order;
use App\Shop\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class OrderCrudController extends AbstractCrudController
{
    public function __construct(
        private OrderService $orderService
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Order')
            ->setEntityLabelInPlural('Orders')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields([
                'orderNumber',
                'customerEmail',
                'customerPhone',
                'molliePaymentId',
                'pickupPointName',
                'pickupLocationCode',
                'storePickupName',
                'storePickupStreet',
                'couponCode',
                'trackingCode',
                'trackingUrl',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW)
            ->disable(Action::DELETE);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(
                ChoiceFilter::new('status')->setChoices([
                    'Pending payment' => Order::STATUS_PENDING_PAYMENT,
                    'Paid' => Order::STATUS_PAID,
                    'Cancelled' => Order::STATUS_CANCELLED,
                    'Shipped' => Order::STATUS_SHIPPED,
                    'Failed' => Order::STATUS_FAILED,
                    'Expired' => Order::STATUS_EXPIRED,
                ])
            )
            ->add(
                ChoiceFilter::new('shippingMethod')->setChoices([
                    'Thuisbezorgen' => Order::SHIPPING_METHOD_HOME,
                    'Afhalen bij PostNL-punt' => Order::SHIPPING_METHOD_PICKUP,
                    'Afhalen in winkel' => Order::SHIPPING_METHOD_STORE_PICKUP,
                ])
            );
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Bestelling');

        yield TextField::new('orderNumber', 'Ordernummer')
            ->hideOnForm();

        yield ChoiceField::new('status', 'Status')
            ->setChoices([
                'Pending payment' => Order::STATUS_PENDING_PAYMENT,
                'Paid' => Order::STATUS_PAID,
                'Cancelled' => Order::STATUS_CANCELLED,
                'Shipped' => Order::STATUS_SHIPPED,
                'Failed' => Order::STATUS_FAILED,
                'Expired' => Order::STATUS_EXPIRED,
            ]);

        yield DateTimeField::new('createdAt', 'Aangemaakt')
            ->hideOnForm();

        yield MoneyField::new('subtotal', 'Subtotaal')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->hideOnForm();

        yield MoneyField::new('discountAmount', 'Korting')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('couponCode', 'Couponcode')
            ->hideOnIndex()
            ->hideOnForm();

        yield MoneyField::new('shippingCost', 'Verzendkosten')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->hideOnIndex()
            ->hideOnForm();

        yield MoneyField::new('total', 'Totaal')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->hideOnForm();

        yield FormField::addPanel('Klant');

        yield EmailField::new('customerEmail', 'E-mail')
            ->hideOnForm();

        yield TelephoneField::new('customerPhone', 'Telefoon')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('molliePaymentId', 'Mollie Payment ID')
            ->hideOnIndex()
            ->hideOnForm();

        yield FormField::addPanel('Verzending');

        yield ChoiceField::new('shippingMethod', 'Verzendmethode')
            ->setChoices([
                'Thuisbezorgen' => Order::SHIPPING_METHOD_HOME,
                'Afhalen bij PostNL-punt' => Order::SHIPPING_METHOD_PICKUP,
                'Afhalen in winkel' => Order::SHIPPING_METHOD_STORE_PICKUP,
            ])
            ->formatValue(static function ($value) {
                return match ($value) {
                    Order::SHIPPING_METHOD_HOME => 'Thuisbezorgen',
                    Order::SHIPPING_METHOD_PICKUP => 'Afhalen bij PostNL-punt',
                    Order::SHIPPING_METHOD_STORE_PICKUP => 'Afhalen in winkel',
                    default => (string) $value,
                };
            });

        yield FormField::addPanel('Track & trace');

        yield TextField::new('trackingCode', 'Trackingcode')
            ->hideOnIndex();

        yield TextField::new('trackingUrl', 'Tracking URL')
            ->hideOnIndex();

        yield DateTimeField::new('shippedAt', 'Verzonden op')
            ->hideOnIndex()
            ->hideOnForm();

        yield FormField::addPanel('Verzendadres')
            ->hideOnIndex();

        yield ArrayField::new('shippingAddress', 'Adres')
            ->hideOnIndex()
            ->hideOnForm();

        yield FormField::addPanel('PostNL afhaalpunt')
            ->hideOnIndex();

        yield TextField::new('pickupPointName', 'Naam afhaalpunt')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('pickupLocationCode', 'Location code')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('pickupRetailNetworkId', 'Retail network ID')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('pickupStreet', 'Straat')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('pickupHouseNumber', 'Huisnummer')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('pickupPostalCode', 'Postcode')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('pickupCity', 'Plaats')
            ->hideOnIndex()
            ->hideOnForm();

        yield FormField::addPanel('Afhalen in winkel')
            ->hideOnIndex();

        yield TextField::new('storePickupName', 'Winkelnaam')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('storePickupStreet', 'Straat')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('storePickupPostalCode', 'Postcode')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('storePickupCity', 'Plaats')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('storePickupCountry', 'Land')
            ->hideOnIndex()
            ->hideOnForm();

        yield FormField::addPanel('Orderregels')
            ->onlyOnDetail();

        yield TextField::new('orderItemsPreview', 'Items')
            ->setTemplatePath('admin/field/order_items.html.twig')
            ->onlyOnDetail()
            ->hideOnForm();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Order) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $originalOrder = $entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance);
        $originalStatus = $originalOrder['status'] ?? null;
        $newStatus = $entityInstance->getStatus();

        if (
            $newStatus === Order::STATUS_SHIPPED
            && $originalStatus !== Order::STATUS_SHIPPED
        ) {
            $this->orderService->markAsShipped(
                $entityInstance,
                $entityInstance->getTrackingCode(),
                $entityInstance->getTrackingUrl()
            );

            return;
        }

        if (
            $newStatus === Order::STATUS_CANCELLED
            && $originalStatus !== Order::STATUS_CANCELLED
        ) {
            $this->orderService->markAsCancelled($entityInstance);

            return;
        }

        if (
            $newStatus === Order::STATUS_FAILED
            && $originalStatus !== Order::STATUS_FAILED
        ) {
            $this->orderService->markAsFailed($entityInstance);

            return;
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}