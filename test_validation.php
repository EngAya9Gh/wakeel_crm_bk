<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/super/v1/tenants/2', 'PATCH', [
    'name' => 'شركة رشد',
    'email' => 'aya.ghouri@rushdai.com',
    'phone' => null,
    'plan' => 'pro',
    'settings' => [
        'whatsapp_provider' => 'https://provider.wakeel.cc/api/v1',
        'whatsapp_api_key' => 'instance_key_...',
        'whatsapp_phone_number' => '+966xxxxxxxxx',
        'whatsapp_webhook_secret' => 'wh_sec_kqcnxh419qnu8dktkzayyr'
    ]
]);

$controller = $app->make(App\Http\Controllers\Api\Super\TenantController::class);

try {
    $controller->update($request, 2);
    echo "Success!\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "Validation failed:\n";
    print_r($e->errors());
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
