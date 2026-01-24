<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];
    
    protected $casts = [
        'first_contact_at' => 'datetime',
        'converted_at' => 'datetime',
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
    public function comments() { return $this->hasMany(Comment::class); }
    public function files() { return $this->hasMany(ClientFile::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function timeline() { return $this->hasMany(ClientTimeline::class); }
    public function procedures() { return $this->hasMany(ClientProcedure::class); }
    
    // Pivot
    public function tags() { return $this->belongsToMany(Tag::class, 'client_tag'); }
}
