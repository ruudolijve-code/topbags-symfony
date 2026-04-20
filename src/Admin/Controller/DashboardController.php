<?php

namespace App\Admin\Controller;

use App\Catalog\Entity\Brand;
use App\Catalog\Entity\Product;
use App\Catalog\Entity\ProductVariant;
use App\Catalog\Entity\Material;
use App\Catalog\Entity\Supplier;
use App\Catalog\Entity\VariantSupply;
use App\Shop\Entity\Order;
use App\Marketing\Entity\NewsletterSubscription;
use App\Shop\Entity\Coupon;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator
    ) {
    }

    #[Route('/admin_dedtwaw', name: 'admin')]
    public function index(): Response
    {
        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(OrderCrudController::class)
                ->generateUrl()
        );
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Topbags Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Shop');
        yield MenuItem::linkToCrud('Orders', 'fa fa-receipt', Order::class);

        yield MenuItem::section('Catalogus');
        yield MenuItem::linkToCrud('Merken', 'fa fa-tag', Brand::class);
        yield MenuItem::linkToCrud('Leveranciers', 'fa fa-truck', Supplier::class);
         yield MenuItem::linkToCrud('Variant supplier overrides', 'fa fa-link', VariantSupply::class);
        yield MenuItem::linkToCrud('Producten', 'fa fa-box', Product::class);
        yield MenuItem::linkToCrud('Varianten', 'fa fa-tags', ProductVariant::class);

        yield MenuItem::section('Marketing');
        yield MenuItem::linkToCrud('Nieuwsbriefinschrijvingen', 'fa fa-envelope', NewsletterSubscription::class);
        yield MenuItem::linkToCrud('Coupons', 'fas fa-percent', Coupon::class);
    }
}