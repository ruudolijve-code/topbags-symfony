<?php

declare(strict_types=1);

namespace App\Marketing\Message;

final readonly class SendNewsletterTestMessage
{
    public function __construct(
        public int $campaignId,
        public string $email,
    ) {
    }
}