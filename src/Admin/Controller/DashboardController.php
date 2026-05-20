<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Entity\AdminUser;
use App\Catalog\Entity\Brand;
use App\Catalog\Entity\Category;
use App\Catalog\Entity\Material;
use App\Catalog\Entity\Product;
use App\Catalog\Entity\ProductVariant;
use App\Catalog\Entity\Supplier;
use App\Catalog\Entity\VariantSupply;
use App\Loyalty\Entity\TravelMilesMember;
use App\Loyalty\Entity\TravelMilesVoucher;
use App\Marketing\Entity\NewsletterCampaign;
use App\Marketing\Entity\NewsletterSubscription;
use App\Seo\Entity\Redirect;
use App\Shop\Entity\Coupon;
use App\Shop\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
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

        /*
         * Winkelmedewerker + volledige admin.
         *
         * Door role_hierarchy geldt:
         * ROLE_ADMIN heeft ook ROLE_STORE.
         */
        if ($this->isGranted('ROLE_STORE')) {
            yield MenuItem::section('Shop');

            yield MenuItem::linkToCrud('Orders', 'fa fa-receipt', Order::class)
                ->setController(OrderCrudController::class);

            yield MenuItem::section('Loyalty');

            yield MenuItem::linkToCrud('Travelmiles leden', 'fa fa-stamp', TravelMilesMember::class)
                ->setController(TravelMilesMemberCrudController::class);

            yield MenuItem::linkToCrud('Travelmiles vouchers', 'fa fa-gift', TravelMilesVoucher::class)
                ->setController(TravelMilesVoucherCrudController::class);

            yield MenuItem::section('Marketing');

            yield MenuItem::linkToCrud('Nieuwsbriefinschrijvingen', 'fa fa-envelope', NewsletterSubscription::class)
                ->setController(NewsletterSubscriptionCrudController::class);
        }

        /*
         * Alleen volledige admin.
         */
        if ($this->isGranted('ROLE_ADMIN')) {
            yield MenuItem::section('Catalogus');

            yield MenuItem::linkToCrud('Merken', 'fa fa-tag', Brand::class)
                ->setController(BrandCrudController::class);

            yield MenuItem::linkToCrud('Leveranciers', 'fa fa-truck', Supplier::class)
                ->setController(SupplierCrudController::class);

            yield MenuItem::linkToCrud('Variant supplier overrides', 'fa fa-link', VariantSupply::class)
                ->setController(VariantSupplyCrudController::class);

            yield MenuItem::linkToCrud('Producten', 'fa fa-box', Product::class)
                ->setController(ProductCrudController::class);

            yield MenuItem::linkToCrud('Varianten', 'fa fa-tags', ProductVariant::class)
                ->setController(ProductVariantCrudController::class);

            yield MenuItem::linkToCrud('Materialen', 'fa fa-layer-group', Material::class)
                ->setController(MaterialCrudController::class);

            yield MenuItem::linkToCrud('Categorieën / menu', 'fa fa-folder-tree', Category::class)
                ->setController(CategoryCrudController::class);

            yield MenuItem::section('Nieuwsbrieven');

            yield MenuItem::linkToCrud('Nieuwsbrieven maken', 'fa fa-envelope-open-text', NewsletterCampaign::class)
                ->setController(NewsletterCampaignCrudController::class);

            yield MenuItem::section('Marketing beheer');

            yield MenuItem::linkToCrud('Coupons', 'fa fa-percent', Coupon::class)
                ->setController(CouponCrudController::class);

            yield MenuItem::section('SEO');

            yield MenuItem::linkToCrud('Redirects', 'fa fa-random', Redirect::class)
                ->setController(RedirectCrudController::class);

            yield MenuItem::section('Beheer');

            yield MenuItem::linkToCrud('Admin gebruikers', 'fa fa-users-gear', AdminUser::class)
                ->setController(AdminUserCrudController::class);
        }
    }
}