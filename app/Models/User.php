<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'team_id',
        'role_id',
        'phone',
        'avatar',
        'is_active',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'permissions_list',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'is_super_admin'    => 'boolean',
        ];
    }

    // Relations
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Helpers
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) return true;
        
        if (!$this->role || !$this->tenant) return false;
        
        $features = $this->tenant->enabledFeatures();
        $feature = explode('.', $permission)[0];
        
        // Sometimes the prefix is not the feature, let's just check the DB to be safe
        // Or we just check if it exists and its group is in features
        return $this->role->permissions()
            ->whereIn('group', $features)
            ->where('name', $permission)
            ->exists();
    }

    public function getPermissionsListAttribute(): array
    {
        if ($this->isSuperAdmin()) {
            return ['*']; // Super admin has all permissions
        }
        
        if (!$this->role || !$this->tenant) return [];
        
        $features = $this->tenant->enabledFeatures();
        
        return $this->role->permissions()
            ->whereIn('group', $features)
            ->pluck('name')
            ->toArray();
    }

    public function commentMentions()
    {
        return $this->belongsToMany(Comment::class, 'comment_mentions')->withTimestamps()->withPivot('read_at');
    }
}
