<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpSyncLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];
}
