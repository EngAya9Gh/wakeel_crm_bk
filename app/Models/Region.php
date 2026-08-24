<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = ['tenant_id', 'name'];

    public function cities()
    {
        return $this->hasMany(City::class);
    }
}
