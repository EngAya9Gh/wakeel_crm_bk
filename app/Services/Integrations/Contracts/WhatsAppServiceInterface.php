<?php

declare(strict_types=1);

namespace App\Services\Integrations\Contracts;

interface WhatsAppServiceInterface
{
    /**
     * Send a WhatsApp message.
     *
     * @param string $to Phone number (with country code)
     * @param string $message Message content
     * @param array|null $media Optional media attachment ['url' => '...', 'type' => 'document/image']
     * @return bool True if sent successfully, false otherwise
     */
    public function send(string $to, string $message, ?array $media = null): bool;

    public function sendList(string $to, string $title, string $body, string $buttonText, array $sections): bool;

    public function sendTemplate(string $to, string $templateId, array $variables): bool;

    public function getThreads(): array;

    public function getThreadMessages(string $threadId): array;

    public function replyToThread(string $threadId, string $content, string $type = 'text'): bool;
}
