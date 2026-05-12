<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminUserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly GoogleAuthenticatorInterface $googleAuthenticator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return AdminUser::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Admin gebruiker')
            ->setEntityLabelInPlural('Admin gebruikers')
            ->setPageTitle(Crud::PAGE_INDEX, 'Admin gebruikers')
            ->setPageTitle(Crud::PAGE_NEW, 'Admin gebruiker toevoegen')
            ->setPageTitle(Crud::PAGE_EDIT, 'Admin gebruiker bewerken')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Admin gebruiker')
            ->setDefaultSort([
                'email' => 'ASC',
            ])
            ->setSearchFields([
                'email',
                'roles',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        $generateTwoFactorSecret = Action::new('generateTwoFactorSecret', 'Genereer 2FA secret', 'fa fa-key')
            ->linkToCrudAction('generateTwoFactorSecret')
            ->displayIf(static fn (AdminUser $user): bool => $user->getId() !== null);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $generateTwoFactorSecret)
            ->add(Crud::PAGE_DETAIL, $generateTwoFactorSecret)
            ->add(Crud::PAGE_EDIT, $generateTwoFactorSecret);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('email')
            ->add('roles');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Gebruiker');

        yield IdField::new('id')
            ->onlyOnIndex();

        yield EmailField::new('email', 'E-mailadres');

        yield ChoiceField::new('roles', 'Rollen')
            ->allowMultipleChoices()
            ->setChoices([
                'Volledige admin' => AdminUser::ROLE_ADMIN,
                'Winkelmedewerker' => AdminUser::ROLE_STORE,
            ])
            ->setHelp('Gebruik “Winkelmedewerker” voor iemand die alleen Orders en Travelmiles nodig heeft.');

        yield TextField::new('plainPassword', 'Nieuw wachtwoord')
            ->setFormType(PasswordType::class)
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false)
            ->setHelp('Verplicht bij nieuwe gebruiker. Laat leeg bij bewerken als je het wachtwoord niet wilt wijzigen.');

        yield FormField::addPanel('Google 2FA');

        yield TextField::new('googleAuthenticatorSecret', 'Google 2FA secret')
            ->hideOnIndex()
            ->setRequired(false)
            ->setHelp('Gebruik de knop “Genereer 2FA secret”. Laat de gebruiker deze sleutel toevoegen in Google Authenticator.');

        yield ArrayField::new('storedRoles', 'Opgeslagen rollen')
            ->onlyOnDetail();

        yield ArrayField::new('roles', 'Effectieve rollen')
            ->onlyOnDetail();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof AdminUser) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        $this->hashPasswordIfProvided($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof AdminUser) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $this->hashPasswordIfProvided($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function generateTwoFactorSecret(
        AdminContext $context,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        $user = $context->getEntity()->getInstance();

        if (!$user instanceof AdminUser) {
            $this->addFlash('danger', 'Geen geldige admin gebruiker gevonden.');

            return $this->redirect($context->getReferrer() ?? $this->generateUrl('admin'));
        }

        $secret = $this->googleAuthenticator->generateSecret();

        $user->setGoogleAuthenticatorSecret($secret);

        $entityManager->flush();

        $this->addFlash(
            'success',
            sprintf(
                '2FA secret aangemaakt voor %s. Voeg deze sleutel toe in Google Authenticator: %s',
                $user->getEmail(),
                $secret
            )
        );

        return $this->redirect($context->getReferrer() ?? $this->generateUrl('admin'));
    }

    private function hashPasswordIfProvided(AdminUser $user): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ($request === null) {
            return;
        }

        $formData = $request->request->all('AdminUser');
        $plainPassword = $formData['plainPassword'] ?? null;

        if (!is_string($plainPassword) || trim($plainPassword) === '') {
            return;
        }

        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $plainPassword)
        );
    }
}