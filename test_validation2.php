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

try {
    $validated = $request->validate([
        'name'      => 'sometimes|string|max:255',
        'slug'      => ['sometimes', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/'],
        'email'     => 'nullable|email|max:255',
        'phone'     => 'nullable|string|max:20',
        'plan'      => ['sometimes', Illuminate\Validation\Rule::in(['basic', 'pro', 'enterprise'])],
        'is_active' => 'boolean',
        'settings'  => 'nullable|array',
    ]);
    
    if ($request->has('settings')) {
        $settingsData = $request->validate([
            'settings.whatsapp_provider'       => 'nullable|string',
            'settings.whatsapp_api_key'        => 'nullable|string',
            'settings.whatsapp_phone_number'   => 'nullable|string',
            'settings.whatsapp_webhook_secret' => 'nullable|string',
        ]);
        print_r($settingsData);
    }
    
    echo "Success!\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "Validation failed:\n";
    print_r($e->errors());
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
