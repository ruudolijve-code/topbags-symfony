<?php

declare(strict_types=1);

namespace App\Marketing\Message;

final readonly class SendNewsletterMessage
{
    public function __construct(
        public int $campaignId,
        public int $subscriptionId,
    ) {
    }
}