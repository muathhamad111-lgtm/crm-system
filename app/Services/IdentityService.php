<?php

namespace App\Services;

use App\Enums\AppRole;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\DB;

class IdentityService
{
    /**
     * Ensure a User has a matching CRM profile (shared uuid) and at least one role.
     * New self-registered accounts default to the `customer` role.
     */
    public function provision(User $user, array $profile = [], ?AppRole $role = null): Profile
    {
        $p = Profile::firstOrNew(['id' => $user->id]);
        $p->id = $user->id;
        $p->email = $user->email;
        $p->full_name = $profile['full_name'] ?? $p->full_name ?? $user->name;
        $p->phone = $profile['phone'] ?? $p->phone;
        $p->account_status = $p->account_status ?: 'active';
        $p->save();

        // Assign default role if the user has none.
        $role ??= $user->isInternalEmail() ? AppRole::SupportStaff : AppRole::Customer;
        if (! UserRole::where('user_id', $user->id)->exists()) {
            UserRole::create([
                'user_id' => $user->id,
                'role' => $role->value,
            ]);
        }

        return $p;
    }

    /** Record an authentication/access event (mirrors auth_access_log). */
    public function logAccess(?string $userId, ?string $email, string $portal, string $outcome, ?string $reason = null, array $meta = []): void
    {
        try {
            DB::table('auth_access_log')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $userId,
                'email' => $email,
                'portal' => $portal,
                'outcome' => $outcome,
                'reason' => $reason,
                'metadata' => json_encode($meta),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // best-effort
        }
    }
}
