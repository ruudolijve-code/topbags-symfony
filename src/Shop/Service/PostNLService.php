<?php

declare(strict_types=1);

namespace App\Shop\Service;

final class PostNLService
{
    public function __construct(
        private string $apiKey,
        private string $customerCode,
        private string $mode = 'production'
    ) {
    }

    /**
     * @return array{
     *   success: bool,
     *   error?: string,
     *   details?: string,
     *   results: array<int, array<string, mixed>>,
     *   raw?: mixed
     * }
     */
    public function getPickupPoints(string $postcode, string $houseNumber): array
    {
        $postcode = strtoupper(str_replace(' ', '', trim($postcode)));
        $houseNumber = trim($houseNumber);

        if (!preg_match('/^[0-9]{4}[A-Z]{2}$/', $postcode)) {
            return [
                'success' => false,
                'error' => 'INVALID_POSTCODE',
                'results' => [],
            ];
        }

        if (!preg_match('/^[0-9]+[A-Z0-9\-]*$/i', $houseNumber)) {
            return [
                'success' => false,
                'error' => 'INVALID_HOUSENUMBER',
                'results' => [],
            ];
        }

        $baseUrl = $this->mode === 'production'
            ? 'https://api.postnl.nl'
            : 'https://api-sandbox.postnl.nl';

        $url = sprintf(
            '%s/shipment/v2_1/locations/nearest?PostalCode=%s&HouseNumber=%s&CountryCode=NL&DeliveryOptions=PGA',
            $baseUrl,
            urlencode($postcode),
            urlencode($houseNumber)
        );

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FAILONERROR => false,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->apiKey,
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch) ?: null;
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($error !== null) {
            return [
                'success' => false,
                'error' => 'CURL_ERROR',
                'details' => $error,
                'results' => [],
            ];
        }

        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'HTTP_' . $httpCode,
                'results' => [],
                'raw' => $response,
            ];
        }

        $json = json_decode((string) $response, true);

        if (!is_array($json)) {
            return [
                'success' => false,
                'error' => 'INVALID_JSON',
                'results' => [],
                'raw' => $response,
            ];
        }

        $locations = $json['GetLocationsResult']['ResponseLocation'] ?? [];

        if (!is_array($locations)) {
            $locations = [];
        }

        $results = [];

        foreach ($locations as $location) {
            if (!is_array($location)) {
                continue;
            }

            $address = is_array($location['Address'] ?? null) ? $location['Address'] : [];

            $results[] = [
                'locationCode' => (string) ($location['LocationCode'] ?? ''),
                'name' => (string) ($location['Name'] ?? 'PostNL-punt'),
                'distance' => isset($location['Distance']) ? (string) $location['Distance'] : '',
                'retailNetworkId' => (string) ($location['RetailNetworkID'] ?? ''),
                'street' => (string) ($address['Street'] ?? ''),
                'houseNumber' => (string) ($address['HouseNr'] ?? ''),
                'postalCode' => (string) ($address['Zipcode'] ?? ''),
                'city' => (string) ($address['City'] ?? ''),
            ];
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }
}