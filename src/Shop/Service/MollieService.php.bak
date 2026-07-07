<?php

namespace App\Shop\Service;

use Mollie\Api\MollieApiClient;

class MollieService
{
    private MollieApiClient $mollie;

    public function __construct(string $mollieApiKey)
    {
        $this->mollie = new MollieApiClient();
        $this->mollie->setApiKey($mollieApiKey);
    }

    public function createPayment(
        float $total,
        string $orderNumber,
        array $metadata,
        string $redirectUrl,
        string $webhookUrl
    ) {
        return $this->mollie->payments->create([
            'amount' => [
                'currency' => 'EUR',
                'value'    => number_format($total, 2, '.', ''),
            ],
            'description' => 'Topbags order ' . $orderNumber,
            'redirectUrl' => $redirectUrl,
            'webhookUrl'  => $webhookUrl,
            'metadata'    => $metadata,
        ]);
    }

    public function getPayment(string $paymentId)
    {
        return $this->mollie->payments->get($paymentId);
    }
}