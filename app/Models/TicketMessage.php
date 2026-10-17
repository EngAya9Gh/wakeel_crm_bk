<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketMessage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    protected $casts = [
        'is_internal' => 'boolean',
        'attachments' => 'array',
    ];

    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function user() { return $this->belongsTo(User::class); }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        $tz = \App\Services\TenantContext::isResolved() 
            ? \App\Services\TenantContext::timezone() 
            : 'Asia/Riyadh';

        return \Carbon\Carbon::parse($date->format('Y-m-d H:i:s'), 'UTC')->setTimezone($tz)->toIso8601String();
    }
}
