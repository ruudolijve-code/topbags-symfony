<?php

namespace App\Admin\Controller;

use App\Marketing\Entity\NewsletterSubscription;
use App\Marketing\Repository\NewsletterSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STORE')]
class NewsletterSubscriptionCrudController extends AbstractCrudController
{
    private const SOURCE_CHOICES = [
        'Topbags webshop' => NewsletterSubscription::SOURCE_TOPBAGS_WEBSHOP,
        'Holtkamp winkel' => NewsletterSubscription::SOURCE_HOLTKAMP_STORE,
        'Handmatig admin' => NewsletterSubscription::SOURCE_ADMIN_MANUAL,
        'Travelmiles member' => NewsletterSubscription::SOURCE_TRAVELMILES_MEMBER,
        'Eerdere bestelling' => NewsletterSubscription::SOURCE_CUSTOMER_ORDER,
    ];

    private const SOURCE_BADGES = [
        NewsletterSubscription::SOURCE_TOPBAGS_WEBSHOP => 'info',
        NewsletterSubscription::SOURCE_HOLTKAMP_STORE => 'success',
        NewsletterSubscription::SOURCE_ADMIN_MANUAL => 'warning',
        NewsletterSubscription::SOURCE_TRAVELMILES_MEMBER => 'primary',
        NewsletterSubscription::SOURCE_CUSTOMER_ORDER => 'secondary',
    ];

    public function __construct(
        private readonly NewsletterSubscriptionRepository $newsletterSubscriptionRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return NewsletterSubscription::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Nieuwsbriefinschrijving')
            ->setEntityLabelInPlural('Nieuwsbriefinschrijvingen')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields([
                'email',
                'source',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isActive', 'Actief'))
            ->add(
                ChoiceFilter::new('source', 'Ingeschreven via')
                    ->setChoices(self::SOURCE_CHOICES)
            );
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm()
            ->hideOnIndex();

        yield EmailField::new('email', 'E-mailadres')
            ->setRequired(true)
            ->setHelp('Een e-mailadres kan maar één keer in de nieuwsbrieflijst staan.');

        yield BooleanField::new('isActive', 'Actief');

        yield ChoiceField::new('source', 'Ingeschreven via')
            ->setChoices(self::SOURCE_CHOICES)
            ->setRequired(false)
            ->setHelp('Geeft aan via welke bron het e-mailadres aan de verzendlijst is toegevoegd.')
            ->renderAsBadges(self::SOURCE_BADGES);

        yield DateTimeField::new('createdAt', 'Ingeschreven op')
            ->hideOnForm();

        yield DateTimeField::new('unsubscribedAt', 'Uitgeschreven op')
            ->hideOnForm();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof NewsletterSubscription) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        $email = mb_strtolower(trim($entityInstance->getEmail()));

        $existing = $this->newsletterSubscriptionRepository->findOneBy([
            'email' => $email,
        ]);

        if ($existing instanceof NewsletterSubscription) {
            $this->addDuplicateEmailFlash($existing);

            return;
        }

        $entityInstance->setEmail($email);
        $entityInstance->ensureUnsubscribeToken();

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof NewsletterSubscription) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $email = mb_strtolower(trim($entityInstance->getEmail()));

        $existing = $this->newsletterSubscriptionRepository->findOneBy([
            'email' => $email,
        ]);

        if (
            $existing instanceof NewsletterSubscription
            && $existing->getId() !== $entityInstance->getId()
        ) {
            $this->addDuplicateEmailFlash($existing);

            return;
        }

        $entityInstance->setEmail($email);
        $entityInstance->ensureUnsubscribeToken();

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function addDuplicateEmailFlash(NewsletterSubscription $existing): void
    {
        if (!$existing->isActive() || $existing->getUnsubscribedAt() !== null) {
            $this->addFlash(
                'warning',
                sprintf(
                    'Dit e-mailadres bestaat al, maar is eerder uitgeschreven: %s. Activeer dit adres alleen opnieuw bij expliciete toestemming.',
                    $existing->getEmail()
                )
            );

            return;
        }

        $this->addFlash(
            'warning',
            sprintf(
                'Dit e-mailadres bestaat al in de nieuwsbrieflijst: %s. Zoek het bestaande adres op en bewerk die inschrijving.',
                $existing->getEmail()
            )
        );
    }
}