<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketCategory extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = ['id'];
    
    protected $casts = [
        'is_active' => 'boolean',
        'sla_hours' => 'integer',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }
}
