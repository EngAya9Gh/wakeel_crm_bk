<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = config('services.gemini.api_key', '');
$baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

$response = \Illuminate\Support\Facades\Http::get("{$baseUrl}?key=" . trim($apiKey));

echo "Status: " . $response->status() . "\n";
$data = $response->json();
foreach ($data['models'] ?? [] as $model) {
    echo $model['name'] . "\n";
}
