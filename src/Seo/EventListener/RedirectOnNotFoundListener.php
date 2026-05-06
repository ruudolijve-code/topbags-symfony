<?php

namespace App\Seo\EventListener;

use App\Seo\Repository\RedirectRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RedirectOnNotFoundListener
{
    private const TARGET_DOMAIN = 'https://topbags.nl';

    public function __construct(
        private readonly RedirectRepository $redirectRepository,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if (!$throwable instanceof NotFoundHttpException) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            return;
        }

        $path = $request->getPathInfo();
        $normalizedPath = rtrim($path, '/') ?: '/';

        $redirect = $this->redirectRepository->findActiveByPath($normalizedPath);

        if (!$redirect && $normalizedPath !== '/') {
            $redirect = $this->redirectRepository->findActiveByPath($normalizedPath . '/');
        }

        if (!$redirect) {
            return;
        }

        $newUrl = trim($redirect->getNewUrl());

        if ($newUrl === '') {
            return;
        }

        if (str_starts_with($newUrl, '/')) {
            $newUrl = self::TARGET_DOMAIN . $newUrl;
        }

        $event->setResponse(new RedirectResponse($newUrl, Response::HTTP_MOVED_PERMANENTLY));
    }
}