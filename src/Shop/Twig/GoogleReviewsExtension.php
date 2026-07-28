<?php

declare(strict_types=1);

namespace App\Shop\Twig;

use App\Shop\Service\GoogleReviewsService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class GoogleReviewsExtension extends AbstractExtension
{
    public function __construct(
        private readonly GoogleReviewsService $googleReviewsService,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'google_business_reviews',
                [$this->googleReviewsService, 'getReviews']
            ),
        ];
    }
}