<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = config('services.gemini.api_key', '');
$baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
$payload = [
    'contents' => [
        ['parts' => [['text' => 'hello']]]
    ]
];

$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Content-Type' => 'application/json',
])->post("{$baseUrl}/gemini-flash-latest:generateContent?key=" . trim($apiKey), $payload);

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
