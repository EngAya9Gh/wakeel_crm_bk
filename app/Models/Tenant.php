<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'logo',
        'is_active',
        'settings',
        'plan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings'  => 'array',
    ];

    // ===================== Relations =====================

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function clientStatuses(): HasMany
    {
        return $this->hasMany(ClientStatus::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(Source::class);
    }

    public function behaviors(): HasMany
    {
        return $this->hasMany(Behavior::class);
    }

    public function invalidReasons(): HasMany
    {
        return $this->hasMany(InvalidReason::class);
    }

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function invoiceTags(): HasMany
    {
        return $this->hasMany(InvoiceTag::class);
    }

    public function commentTypes(): HasMany
    {
        return $this->hasMany(CommentType::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the enabled features/modules for this tenant.
     * This is used to lock permissions if the tenant does not have the feature.
     */
    public function enabledFeatures(): array
    {
        // Core features from config
        $defaults = collect(config('features.available_modules', []))
            ->where('is_core', true)
            ->keys()
            ->toArray();
        
        // Custom features enabled for this tenant
        $custom = $this->settings['features'] ?? null;
        
        // If features were never explicitly set, fallback to the plan's default modules
        if ($custom === null) {
            $custom = config("features.plans.{$this->plan}.modules", []);
        }
        
        // Backwards compatibility
        if (!empty($this->settings['whatsapp_api_key']) && !in_array('whatsapp', $custom)) {
            $custom[] = 'whatsapp';
        }
        if (!empty($this->settings['invoices_enabled']) && !in_array('invoices', $custom)) {
            $custom[] = 'invoices';
        }
        
        return array_values(array_unique(array_merge($defaults, $custom)));
    }
}
