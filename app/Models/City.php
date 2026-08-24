<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = ['tenant_id', 'region_id', 'name'];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
