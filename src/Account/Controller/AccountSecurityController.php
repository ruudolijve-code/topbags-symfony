<?php

namespace App\Account\Controller;

use App\Account\Entity\CustomerUser;
use App\Account\Form\CustomerRegisterType;
use App\Shop\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AccountSecurityController extends AbstractController
{
    #[Route('/account', name: 'account_dashboard', methods: ['GET'])]
    public function dashboard(OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        if (!$user instanceof CustomerUser) {
            throw $this->createAccessDeniedException();
        }

        $orders = $orderRepository->findByCustomerUser($user);

        return $this->render('account/dashboard.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/account/login', name: 'account_login', methods: ['GET', 'POST'])]
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils
    ): Response {
        if ($this->getUser() !== null) {
            $redirectTo = (string) $request->query->get('redirect');

            if ($redirectTo !== '') {
                return $this->redirect($redirectTo);
            }

            return $this->redirectToRoute('account_dashboard');
        }

        return $this->render('account/login.html.twig', [
            'lastUsername' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'redirect' => (string) $request->query->get('redirect', ''),
        ]);
    }

    #[Route('/account/register', name: 'account_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('account_dashboard');
        }

        $user = new CustomerUser();
        $form = $this->createForm(CustomerRegisterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $plainPasswordRepeat = (string) $form->get('plainPasswordRepeat')->getData();

            if ($plainPassword !== $plainPasswordRepeat) {
                $form->get('plainPasswordRepeat')->addError(
                    new FormError('De wachtwoorden komen niet overeen.')
                );
            } else {
                $normalizedEmail = mb_strtolower(trim((string) $user->getEmail()));

                $existingUser = $em->getRepository(CustomerUser::class)->findOneBy([
                    'email' => $normalizedEmail,
                ]);

                if ($existingUser !== null) {
                    $form->get('email')->addError(
                        new FormError('Er bestaat al een account met dit e-mailadres.')
                    );
                } else {
                    $user->setEmail($normalizedEmail);
                    $user->setPassword(
                        $passwordHasher->hashPassword($user, $plainPassword)
                    );

                    $em->persist($user);
                    $em->flush();

                    $this->addFlash('success', 'Je account is aangemaakt. Je kunt nu inloggen.');

                    $redirectTo = (string) $request->query->get('redirect');

                    if ($redirectTo !== '') {
                        return $this->redirectToRoute('account_login', [
                            'redirect' => $redirectTo,
                        ]);
                    }

                    return $this->redirectToRoute('account_login');
                }
            }
        }

        return $this->render('account/register.html.twig', [
            'form' => $form->createView(),
            'redirect' => (string) $request->query->get('redirect', ''),
        ]);
    }

    #[Route('/account/logout', name: 'customer_logout', methods: ['GET', 'POST'])]
    public function logout(): never
    {
        throw new \LogicException('Deze route wordt afgehandeld door de logout-instelling van de customer-firewall.');
    }
}