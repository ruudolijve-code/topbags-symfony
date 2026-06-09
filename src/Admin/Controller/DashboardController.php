<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Entity\AdminUser;
use App\Catalog\Entity\Brand;
use App\Catalog\Entity\Category;
use App\Catalog\Entity\Color;
use App\Catalog\Entity\Material;
use App\Catalog\Entity\Product;
use App\Catalog\Entity\ProductVariant;
use App\Catalog\Entity\Supplier;
use App\Catalog\Entity\VariantSupply;
use App\Guide\Entity\Airline;
use App\Guide\Entity\AirlineBaggageRule;
use App\Guide\Entity\AirlineTicketType;
use App\Guide\Entity\Faq;
use App\Guide\Entity\TravelAgencyLandingPage;
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
         *
         * Winkelmedewerkers mogen:
         * - orders zien/beheren volgens OrderCrudController
         * - producten bekijken
         * - varianten bekijken en voorraad/backorder aanpassen
         * - Travelmiles beheren
         * - nieuwsbriefinschrijvingen beheren
         */
        if ($this->isGranted('ROLE_STORE')) {
            yield MenuItem::subMenu('Shop', 'fa fa-store')->setSubItems([
                MenuItem::linkToCrud('Orders', 'fa fa-receipt', Order::class)
                    ->setController(OrderCrudController::class),
            ]);

            yield MenuItem::subMenu('Catalogus', 'fa fa-boxes-stacked')->setSubItems([
                MenuItem::linkToCrud('Producten bekijken', 'fa fa-box', Product::class)
                    ->setController(ProductCrudController::class),

                MenuItem::linkToCrud('Voorraad / varianten', 'fa fa-tags', ProductVariant::class)
                    ->setController(ProductVariantCrudController::class),
            ]);

            yield MenuItem::subMenu('Loyalty', 'fa fa-stamp')->setSubItems([
                MenuItem::linkToCrud('Travelmiles leden', 'fa fa-users', TravelMilesMember::class)
                    ->setController(TravelMilesMemberCrudController::class),

                MenuItem::linkToCrud('Travelmiles vouchers', 'fa fa-gift', TravelMilesVoucher::class)
                    ->setController(TravelMilesVoucherCrudController::class),
            ]);

            yield MenuItem::subMenu('Marketing', 'fa fa-envelope')->setSubItems([
                MenuItem::linkToCrud('Nieuwsbriefinschrijvingen', 'fa fa-envelope', NewsletterSubscription::class)
                    ->setController(NewsletterSubscriptionCrudController::class),
            ]);
        }

        /*
         * Alleen volledige admin.
         *
         * Producten en varianten staan hierboven al onder ROLE_STORE,
         * dus hier alleen de aanvullende catalogusbeheer-onderdelen.
         */
        if ($this->isGranted('ROLE_ADMIN')) {
            yield MenuItem::subMenu('Catalogus beheer', 'fa fa-screwdriver-wrench')->setSubItems([
                MenuItem::linkToCrud('Merken', 'fa fa-tag', Brand::class)
                    ->setController(BrandCrudController::class),

                MenuItem::linkToCrud('Categorieën / menu', 'fa fa-folder-tree', Category::class)
                    ->setController(CategoryCrudController::class),

                MenuItem::linkToCrud('Kleuren', 'fa fa-palette', Color::class)
                    ->setController(ColorCrudController::class),

                MenuItem::linkToCrud('Materialen', 'fa fa-layer-group', Material::class)
                    ->setController(MaterialCrudController::class),
            ]);

            yield MenuItem::subMenu('Leveranciers', 'fa fa-truck')->setSubItems([
                MenuItem::linkToCrud('Leveranciers', 'fa fa-truck', Supplier::class)
                    ->setController(SupplierCrudController::class),

                MenuItem::linkToCrud('Variant supplier overrides', 'fa fa-link', VariantSupply::class)
                    ->setController(VariantSupplyCrudController::class),
            ]);

            yield MenuItem::subMenu('Nieuwsbrieven', 'fa fa-envelope-open-text')->setSubItems([
                MenuItem::linkToCrud('Nieuwsbrieven maken', 'fa fa-envelope-open-text', NewsletterCampaign::class)
                    ->setController(NewsletterCampaignCrudController::class),
            ]);

            yield MenuItem::subMenu('Marketing beheer', 'fa fa-bullhorn')->setSubItems([
                MenuItem::linkToCrud('Coupons', 'fa fa-percent', Coupon::class)
                    ->setController(CouponCrudController::class),
            ]);

            yield MenuItem::subMenu('SEO', 'fa fa-magnifying-glass-chart')->setSubItems([
                MenuItem::linkToCrud('Redirects', 'fa fa-random', Redirect::class)
                    ->setController(RedirectCrudController::class),

                MenuItem::linkToCrud('Reisbureau pagina’s', 'fa fa-map-location-dot', TravelAgencyLandingPage::class)
                    ->setController(TravelAgencyLandingPageCrudController::class),
            ]);

            yield MenuItem::subMenu('Bagagegids', 'fa fa-suitcase-rolling')->setSubItems([
                MenuItem::linkToCrud('Vliegmaatschappijen', 'fa fa-plane', Airline::class)
                    ->setController(AirlineCrudController::class),

                MenuItem::linkToCrud('Tickettypes', 'fa fa-ticket', AirlineTicketType::class)
                    ->setController(AirlineTicketTypeCrudController::class),

                MenuItem::linkToCrud('Bagageregels', 'fa fa-suitcase-rolling', AirlineBaggageRule::class)
                    ->setController(AirlineBaggageRuleCrudController::class),

                MenuItem::linkToCrud('FAQ’s', 'fa fa-question-circle', Faq::class)
                    ->setController(FaqCrudController::class),
            ]);

            yield MenuItem::subMenu('Beheer', 'fa fa-gear')->setSubItems([
                MenuItem::linkToCrud('Admin gebruikers', 'fa fa-users-gear', AdminUser::class)
                    ->setController(AdminUserCrudController::class),
            ]);
        }
    }
}