<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Integrations\Contracts\SmsServiceInterface;
use App\Services\Integrations\Contracts\WhatsAppServiceInterface;
use App\Services\Integrations\SmsService;
use App\Services\Integrations\WhatsAppService;
use Illuminate\Support\ServiceProvider;

class IntegrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(SmsServiceInterface::class, SmsService::class);
        $this->app->bind(WhatsAppServiceInterface::class, WhatsAppService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
