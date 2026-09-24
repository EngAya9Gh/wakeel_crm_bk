<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EvaluationLink extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = ['id'];
    
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_user_id'); }
    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function evaluation() { return $this->belongsTo(Evaluation::class); }
}
