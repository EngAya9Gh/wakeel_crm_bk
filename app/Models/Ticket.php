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
        'rating' => 'integer',
    ];

    protected $appends = ['rating'];

    public function client() { return $this->belongsTo(Client::class); }
    public function creator() { return $this->belongsTo(User::class, 'user_id'); }
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function category() { return $this->belongsTo(TicketCategory::class, 'category_id'); }
    public function messages() { return $this->hasMany(TicketMessage::class); }
    public function evaluations() { return $this->hasMany(Evaluation::class); }

    public function scopeByProviderThread($query, string $threadId)
    {
        return $query->where(function ($q) use ($threadId) {
            $q->where('provider_thread_id', $threadId)
              ->orWhereJsonContains('metadata->provider_thread_id', $threadId);
        });
    }

    public function scopeByProviderTicket($query, string $providerTicketId)
    {
        return $query->where(function ($q) use ($providerTicketId) {
            $q->where('provider_ticket_id', $providerTicketId)
              ->orWhereJsonContains('metadata->provider_ticket_id', $providerTicketId);
        });
    }

    public function getRatingAttribute(): ?int
    {
        if (isset($this->attributes['rating']) && $this->attributes['rating'] !== null) {
            return (int) $this->attributes['rating'];
        }

        if ($this->relationLoaded('evaluations') && $this->evaluations->isNotEmpty()) {
            return (int) $this->evaluations->first()->rating;
        }

        $metaRating = $this->metadata['rating'] ?? null;
        return $metaRating !== null ? (int) $metaRating : null;
    }
}
