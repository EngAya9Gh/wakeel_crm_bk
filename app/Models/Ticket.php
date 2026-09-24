<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $guarded = ['id'];
    
    protected $casts = [
        'sla_due_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function creator() { return $this->belongsTo(User::class, 'user_id'); }
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function category() { return $this->belongsTo(TicketCategory::class, 'category_id'); }
    public function messages() { return $this->hasMany(TicketMessage::class); }
    public function evaluations() { return $this->hasMany(Evaluation::class); }
}
