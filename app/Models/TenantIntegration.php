<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'platform',
        'is_active',
        'credentials',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credentials' => 'encrypted:array', // Encrypt the credentials json array automatically
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
