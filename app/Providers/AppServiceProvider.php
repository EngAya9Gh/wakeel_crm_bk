<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Blade directive to fix Arabic text for PDF
        \Illuminate\Support\Facades\Blade::directive('ar', function ($expression) {
            return "<?php echo (new \ArPHP\I18N\Arabic('Glyphs'))->utf8Glyphs($expression); ?>";
        });

        // Custom Password Reset URL for Mobile/Frontend
        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.url') . "/reset-password?token={$token}&email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
