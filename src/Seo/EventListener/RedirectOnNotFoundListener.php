<?php

namespace App\Seo\EventListener;

use App\Seo\Repository\RedirectRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RedirectOnNotFoundListener
{
    public function __construct(
        private RedirectRepository $redirectRepository,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if (!$throwable instanceof NotFoundHttpException) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->isMethod('GET')) {
            return;
        }

        $path = $request->getPathInfo();
        $path = rtrim($path, '/');

        if ($path === '') {
            $path = '/';
        }

        $redirect = $this->redirectRepository->findActiveByPath($path);

        if (!$redirect) {
            return;
        }

        $event->setResponse(new RedirectResponse($redirect->getNewUrl(), 301));
    }
}