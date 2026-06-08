<?php

declare(strict_types=1);

namespace App\Services\Integrations\ERP\Contracts;

class ErpSyncResult
{
    public function __construct(
        public bool $success,
        public ?string $message = null,
        public ?string $erpId = null,
        public array $data = []
    ) {}
}
