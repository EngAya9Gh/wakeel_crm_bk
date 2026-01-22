<?php

declare(strict_types=1);

namespace App\Services\Integrations\Contracts;

interface SmsServiceInterface
{
    /**
     * Send an SMS message.
     *
     * @param string $to Phone number (with country code)
     * @param string $message Message content
     * @return bool True if sent successfully, false otherwise
     */
    public function send(string $to, string $message): bool;
}
