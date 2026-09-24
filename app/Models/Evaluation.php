<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Evaluation extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = ['id'];
    
    protected $casts = [
        'rating' => 'integer',
        'metadata' => 'array',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_user_id'); }
    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function links() { return $this->hasMany(EvaluationLink::class); }
}
