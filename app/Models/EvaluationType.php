<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationType extends Model
{
    use \App\Traits\BelongsToTenant;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
