<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientTimeline extends Model
{
    protected $table = 'client_timeline';
    protected $guarded = [];
    protected $casts = ['metadata' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
