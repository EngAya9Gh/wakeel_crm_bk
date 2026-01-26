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
