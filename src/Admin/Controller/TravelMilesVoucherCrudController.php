<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Loyalty\Entity\TravelMilesVoucher;
use App\Loyalty\Service\TravelMilesVoucherFactory;
use App\Loyalty\Service\TravelMilesVoucherMailer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class TravelMilesVoucherCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly TravelMilesVoucherFactory $voucherFactory,
        private readonly TravelMilesVoucherMailer $voucherMailer,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return TravelMilesVoucher::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Travelmiles voucher')
            ->setEntityLabelInPlural('Travelmiles vouchers')
            ->setPageTitle(Crud::PAGE_INDEX, 'Travelmiles vouchers')
            ->setPageTitle(Crud::PAGE_NEW, 'Travelmiles voucher aanmaken')
            ->setPageTitle(Crud::PAGE_EDIT, 'Travelmiles voucher bewerken')
            ->setDefaultSort([
                'createdAt' => 'DESC',
            ])
            ->setSearchFields([
                'code',
                'campaign',
                'member.email',
                'member.firstName',
                'member.lastName',
                'coupon.code',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        $sendGiftcard = Action::new('sendGiftcard', 'Verstuur giftcard', 'fa fa-paper-plane')
            ->linkToCrudAction('sendGiftcard')
            ->displayIf(static function (TravelMilesVoucher $voucher): bool {
                return $voucher->getStatus() !== TravelMilesVoucher::STATUS_REDEEMED
                    && $voucher->getStatus() !== TravelMilesVoucher::STATUS_CANCELLED;
            });

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $sendGiftcard)
            ->add(Crud::PAGE_DETAIL, $sendGiftcard);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('member')
            ->add('status')
            ->add('campaign')
            ->add('createdAt')
            ->add('sentAt')
            ->add('redeemedAt')
            ->add('expiresAt');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Voucher');

        yield AssociationField::new('member', 'Travelmiles lid')
            ->setRequired(true)
            ->setHelp('Kies het Travelmiles-lid waarvoor deze voucher is.');

        yield TextField::new('code', 'Code')
            ->setHelp('Wordt automatisch aangemaakt. Bij opslaan wordt hiermee ook een coupon voor checkout aangemaakt.');

        yield MoneyField::new('amount', 'Waarde')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setHelp('Voor de welkomstactie gebruik je 10.00.');

        yield TextField::new('currency', 'Valuta')
            ->hideOnIndex()
            ->setHelp('Standaard EUR.');

        yield ChoiceField::new('status', 'Status')
            ->setChoices([
                'Aangemaakt' => TravelMilesVoucher::STATUS_CREATED,
                'Verstuurd' => TravelMilesVoucher::STATUS_SENT,
                'Gebruikt' => TravelMilesVoucher::STATUS_REDEEMED,
                'Verlopen' => TravelMilesVoucher::STATUS_EXPIRED,
                'Geannuleerd' => TravelMilesVoucher::STATUS_CANCELLED,
            ]);

        yield TextField::new('campaign', 'Campagne')
            ->setRequired(false)
            ->setHelp('Bijvoorbeeld: Welkomstvoucher, Moederdag 2026 of Verjaardag.');

        yield AssociationField::new('coupon', 'Gekoppelde checkout-coupon')
            ->onlyOnDetail();

        yield FormField::addPanel('Statusdatums');

        yield DateTimeField::new('expiresAt', 'Geldig tot')
            ->setRequired(false)
            ->setHelp('Standaard 6 maanden geldig vanaf aanmaken.');

        yield DateTimeField::new('sentAt', 'Verstuurd op')
            ->setRequired(false);

        yield DateTimeField::new('redeemedAt', 'Gebruikt op')
            ->setRequired(false);

        yield DateTimeField::new('cancelledAt', 'Geannuleerd op')
            ->setRequired(false)
            ->hideOnIndex();

        yield FormField::addPanel('Notities');

        yield TextareaField::new('notes', 'Notities')
            ->hideOnIndex()
            ->setRequired(false);

        yield DateTimeField::new('createdAt', 'Aangemaakt op')
            ->setFormat('dd-MM-yyyy HH:mm')
            ->hideOnForm();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof TravelMilesVoucher) {
            $this->voucherFactory->syncCouponWithVoucher($entityInstance);

            if ($entityInstance->getCoupon() !== null) {
                $entityManager->persist($entityInstance->getCoupon());
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof TravelMilesVoucher) {
            $this->voucherFactory->syncCouponWithVoucher($entityInstance);

            if ($entityInstance->getCoupon() !== null) {
                $entityManager->persist($entityInstance->getCoupon());
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function sendGiftcard(AdminContext $context): RedirectResponse
    {
        $voucher = $context->getEntity()->getInstance();

        if (!$voucher instanceof TravelMilesVoucher) {
            $this->addFlash('danger', 'Geen geldige Travelmiles voucher gevonden.');

            return $this->redirect($context->getReferrer() ?? $this->generateUrl('admin'));
        }

        try {
            $this->voucherFactory->syncCouponWithVoucher($voucher);
            $this->voucherMailer->sendGiftcard($voucher);

            $this->addFlash(
                'success',
                sprintf('Giftcard %s is verstuurd.', $voucher->getCode())
            );
        } catch (\Throwable $exception) {
            $this->addFlash(
                'danger',
                'Giftcard kon niet worden verstuurd: ' . $exception->getMessage()
            );
        }

        return $this->redirect($context->getReferrer() ?? $this->generateUrl('admin'));
    }
}