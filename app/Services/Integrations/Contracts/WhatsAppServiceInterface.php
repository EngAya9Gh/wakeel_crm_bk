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

    /**
     * Download media from WhatsApp Provider.
     *
     * @param string $url The provider's media URL
     * @return array|null Returns ['body' => string, 'contentType' => string] or null on failure
     */
    public function getMedia(string $url): ?array;

    /**
     * Upload a media file to the thread and return the public URL.
     *
     * @param string $threadId
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $mediaType  image | audio | document | video
     * @return string|null
     */
    public function uploadThreadMedia(string $threadId, \Illuminate\Http\UploadedFile $file, string $mediaType = 'image'): ?string;
}
