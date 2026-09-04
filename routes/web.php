<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/fix-storage', function () {
    $target = storage_path('app/public');
    $link = public_path('storage');

    echo "<h2>Storage Fixer</h2>";
    echo "Target: $target<br>";
    echo "Link: $link<br><hr>";

    if (file_exists($link)) {
        unlink($link);
        echo "Old link removed.<br>";
    }

    symlink($target, $link);
    echo "✅ Symlink created.<br>";
    
    echo "<hr>Check if public is readable: " . (is_readable($target) ? 'Yes' : 'No');
});

// =====================================================================
// SUPER ADMIN WEB ROUTES
// =====================================================================
Route::prefix('super')->group(function () {
    // Guest Routes
    Route::middleware('guest')->group(function () {
        Route::get('login', [\App\Http\Controllers\Super\Web\AuthController::class, 'showLogin'])->name('super.login');
        Route::post('login', [\App\Http\Controllers\Super\Web\AuthController::class, 'login']);
    });

    // Protected Routes
    Route::middleware('auth')->group(function () {
        Route::post('logout', [\App\Http\Controllers\Super\Web\AuthController::class, 'logout'])->name('super.logout');
        
        Route::get('dashboard', [\App\Http\Controllers\Super\Web\DashboardController::class, 'index'])->name('super.dashboard');
        
        Route::get('tenants', [\App\Http\Controllers\Super\Web\TenantController::class, 'index'])->name('super.tenants.index');
        Route::post('tenants', [\App\Http\Controllers\Super\Web\TenantController::class, 'store'])->name('super.tenants.store');
        Route::get('tenants/{tenant}', [\App\Http\Controllers\Super\Web\TenantController::class, 'show'])->name('super.tenants.show');
        Route::put('tenants/{tenant}/settings', [\App\Http\Controllers\Super\Web\TenantController::class, 'updateSettings'])->name('super.tenants.settings.update');
        Route::post('tenants/{tenant}/api-keys', [\App\Http\Controllers\Super\Web\TenantController::class, 'generateApiKey'])->name('super.tenants.api-keys.store');
        Route::delete('tenants/{tenant}/api-keys/{apiKey}', [\App\Http\Controllers\Super\Web\TenantController::class, 'revokeApiKey'])->name('super.tenants.api-keys.destroy');
    });
});
