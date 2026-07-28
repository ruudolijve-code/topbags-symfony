<?php

declare(strict_types=1);

namespace App\Shop\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GoogleReviewsService
{
    private const CACHE_KEY = 'google_business_reviews';
    private const CACHE_TTL = 86400;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly string $apiKey,
        private readonly string $placeId,
    ) {
    }

    /**
     * @return array{
     *     name: string,
     *     rating: float,
     *     reviewCount: int,
     *     googleMapsUrl: string|null,
     *     reviews: list<array{
     *         authorName: string,
     *         authorUrl: string|null,
     *         authorPhoto: string|null,
     *         rating: float,
     *         text: string,
     *         relativeTime: string|null,
     *         reviewUrl: string|null
     *     }>
     * }|null
     */
    public function getReviews(): ?array
    {
        if ($this->apiKey === '' || $this->placeId === '') {
            return null;
        }

        $cacheItem = $this->cache->getItem(self::CACHE_KEY);

        if ($cacheItem->isHit()) {
            $cached = $cacheItem->get();

            return is_array($cached) ? $cached : null;
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                sprintf(
                    'https://places.googleapis.com/v1/places/%s',
                    rawurlencode($this->placeId)
                ),
                [
                    'headers' => [
                        'X-Goog-Api-Key' => $this->apiKey,
                        'X-Goog-FieldMask' => implode(',', [
                            'displayName',
                            'rating',
                            'userRatingCount',
                            'googleMapsUri',
                            'reviews',
                        ]),
                        'Accept-Language' => 'nl-NL,nl;q=0.9',
                    ],
                    'timeout' => 8,
                ]
            );

            $data = $response->toArray();

            $result = [
                'name' => (string) (
                    $data['displayName']['text']
                    ?? 'Holtkamp Lederwaren | topbags.nl'
                ),
                'rating' => (float) ($data['rating'] ?? 0),
                'reviewCount' => (int) ($data['userRatingCount'] ?? 0),
                'googleMapsUrl' => isset($data['googleMapsUri'])
                    ? (string) $data['googleMapsUri']
                    : null,
                'reviews' => $this->normalizeReviews(
                    is_array($data['reviews'] ?? null)
                        ? $data['reviews']
                        : []
                ),
            ];

            $cacheItem->set($result);
            $cacheItem->expiresAfter(self::CACHE_TTL);
            $this->cache->save($cacheItem);

            return $result;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<int, mixed> $reviews
     *
     * @return list<array{
     *     authorName: string,
     *     authorUrl: string|null,
     *     authorPhoto: string|null,
     *     rating: float,
     *     text: string,
     *     relativeTime: string|null,
     *     reviewUrl: string|null
     * }>
     */
    private function normalizeReviews(array $reviews): array
    {
        $normalized = [];

        foreach (array_slice($reviews, 0, 3) as $review) {
            if (!is_array($review)) {
                continue;
            }

            $originalText = $review['originalText']['text'] ?? null;
            $translatedText = $review['text']['text'] ?? null;

            $text = is_string($originalText) && trim($originalText) !== ''
                ? trim($originalText)
                : (
                    is_string($translatedText)
                        ? trim($translatedText)
                        : ''
                );

            $normalized[] = [
                'authorName' => (string) (
                    $review['authorAttribution']['displayName']
                    ?? 'Google-gebruiker'
                ),
                'authorUrl' => isset($review['authorAttribution']['uri'])
                    ? (string) $review['authorAttribution']['uri']
                    : null,
                'authorPhoto' => isset($review['authorAttribution']['photoUri'])
                    ? (string) $review['authorAttribution']['photoUri']
                    : null,
                'rating' => (float) ($review['rating'] ?? 0),
                'text' => $text,
                'relativeTime' => isset($review['relativePublishTimeDescription'])
                    ? (string) $review['relativePublishTimeDescription']
                    : null,
                'reviewUrl' => isset($review['googleMapsUri'])
                    ? (string) $review['googleMapsUri']
                    : null,
            ];
        }

        return $normalized;
    }
}