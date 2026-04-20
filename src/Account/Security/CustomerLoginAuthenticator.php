<?php

namespace App\Account\Security;

use App\Account\Repository\CustomerUserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

final class CustomerLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    public const LOGIN_ROUTE = 'account_login';

    public function __construct(
        private RouterInterface $router,
        private CustomerUserRepository $userRepository
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = (string) $request->request->get('email', '');

        $request->getSession()->set(
            SecurityRequestAttributes::LAST_USERNAME,
            $email
        );

        return new Passport(
            new UserBadge($email, function (string $userIdentifier) {
                return $this->userRepository->findOneBy([
                    'email' => mb_strtolower(trim($userIdentifier)),
                ]);
            }),
            new PasswordCredentials((string) $request->request->get('password', '')),
            [
                new CsrfTokenBadge(
                    'authenticate',
                    (string) $request->request->get('_csrf_token')
                ),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        $session = $request->getSession();

        if ($session !== null) {
            $redirect = $session->get('_security.customer.target_path');

            if (is_string($redirect) && $redirect !== '') {
                $session->remove('_security.customer.target_path');

                return new RedirectResponse($redirect);
            }
        }

        $redirect = (string) $request->request->get('redirect', '');

        if ($redirect !== '') {
            return new RedirectResponse($redirect);
        }

        return new RedirectResponse(
            $this->router->generate('account_dashboard')
        );
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate(self::LOGIN_ROUTE);
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): RedirectResponse {
        $redirect = (string) $request->request->get('redirect', '');

        if ($redirect !== '') {
            return new RedirectResponse(
                $this->router->generate(self::LOGIN_ROUTE, [
                    'redirect' => $redirect,
                ])
            );
        }

        return new RedirectResponse(
            $this->router->generate(self::LOGIN_ROUTE)
        );
    }
}