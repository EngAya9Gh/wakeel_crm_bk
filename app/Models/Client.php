<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $guarded = ['id'];
    
    protected $casts = [
        'first_contact_at' => 'datetime',
        'converted_at'     => 'datetime',
        'erp_synced_at'    => 'datetime',
    ];

    // Relations Settings
    public function status() { return $this->belongsTo(ClientStatus::class); }
    public function source() { return $this->belongsTo(Source::class); }
    public function behavior() { return $this->belongsTo(Behavior::class); }
    public function invalidReason() { return $this->belongsTo(InvalidReason::class); }
    public function region() { return $this->belongsTo(Region::class); }
    public function city() { return $this->belongsTo(City::class); }

    // Relations Users
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }

    // Relations Children
    public function contacts() { return $this->hasMany(ClientContact::class); }
    public function comments() { return $this->hasMany(Comment::class); }
    public function files() { return $this->hasMany(ClientFile::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function timeline() { return $this->hasMany(ClientTimeline::class); }
    public function procedures() { return $this->hasMany(ClientProcedure::class); }
    public function tickets() { return $this->hasMany(Ticket::class); }
    public function evaluations() { return $this->hasMany(Evaluation::class); }
    public function evaluationLinks() { return $this->hasMany(EvaluationLink::class); }
    
    // Pivot
    public function tags() { return $this->belongsToMany(Tag::class, 'client_tag'); }

    /**
     * Find a client by phone flexibly across various formatting variants
     * (+966..., 966..., 05..., 00966...) and in both clients and client_contacts.
     */
    public static function findByPhoneFlexible(int $tenantId, ?string $rawPhone): ?self
    {
        if (empty($rawPhone)) {
            return null;
        }

        $rawPhone = trim($rawPhone);
        $digits = preg_replace('/[^0-9]/', '', $rawPhone);
        if (empty($digits)) {
            return null;
        }

        $variants = array_unique(array_filter([
            $rawPhone,
            $digits,
            '+' . $digits,
            '00' . $digits,
            (str_starts_with($digits, '966') && strlen($digits) === 12) ? '0' . substr($digits, 3) : null,
            (str_starts_with($digits, '966') && strlen($digits) === 12) ? substr($digits, 3) : null,
            (str_starts_with($digits, '05') && strlen($digits) === 10) ? '966' . substr($digits, 1) : null,
            (str_starts_with($digits, '05') && strlen($digits) === 10) ? '+966' . substr($digits, 1) : null,
        ]));

        $last9 = strlen($digits) >= 9 ? substr($digits, -9) : (strlen($digits) >= 7 ? substr($digits, -7) : null);

        // 1. Search in Clients table
        $client = self::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($variants, $last9) {
                $q->whereIn('phone', $variants);
                if ($last9) {
                    $q->orWhere('phone', 'like', '%' . $last9);
                }
            })
            ->first();

        if ($client) {
            return $client;
        }

        // 2. Search in ClientContacts table
        $contact = ClientContact::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($variants, $last9) {
                $q->whereIn('phone', $variants);
                if ($last9) {
                    $q->orWhere('phone', 'like', '%' . $last9);
                }
            })
            ->first();

        return $contact?->client;
    }
}
