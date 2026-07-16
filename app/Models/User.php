<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'name', 'email', 'password', 'avatar'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile()
    {
        return $this->hasOne(Profile::class, 'id', 'id');
    }

    public function roles()
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    /** Array of role keys assigned to this user. */
    public function roleKeys(): array
    {
        return $this->roles()->pluck('role')->all();
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roleKeys(), true);
    }

    /** Any non-customer role. */
    public function hasStaffRole(): bool
    {
        return collect($this->roleKeys())->contains(fn ($r) => $r !== 'customer');
    }

    public function isInternalEmail(): bool
    {
        $domain = config('services.staff_domain', 'altqniah.sa');

        return str_ends_with(strtolower($this->email ?? ''), '@'.$domain);
    }

    public function isSuspended(): bool
    {
        return (bool) ($this->profile?->suspended ?? false);
    }

    /** Staff = has staff role AND internal email AND not suspended. */
    public function isStaff(): bool
    {
        return $this->hasStaffRole() && $this->isInternalEmail() && ! $this->isSuspended();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('system_admin') && $this->isInternalEmail() && ! $this->isSuspended();
    }

    /** Capability check via the role_permissions matrix (cached 5 min). */
    public function hasCapability(string $capability): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        $caps = Cache::remember("caps:{$this->id}", 300, function () {
            return DB::table('role_permissions')
                ->whereIn('role', $this->roleKeys())
                ->where('allowed', true)
                ->pluck('capability')
                ->unique()
                ->all();
        });

        return in_array($capability, $caps, true);
    }
}
