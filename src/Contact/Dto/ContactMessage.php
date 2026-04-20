<?php

namespace App\Contact\Dto;

final class ContactMessage
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly string $subject,
        public readonly string $message,
        public readonly ?string $source = null, // bv. "contact-page" / "guide"
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null,
    ) {}
}