<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $roles = [];
        $isStaff = false;
        $isAdmin = false;
        $profile = null;

        if ($user) {
            if (Schema::hasTable('user_roles')) {
                $roles = DB::table('user_roles')->where('user_id', $user->id)->pluck('role')->all();
            }
            $internalDomain = str_ends_with(strtolower($user->email ?? ''), '@altqniah.sa');
            $suspended = false;
            if (Schema::hasTable('profiles')) {
                $profile = DB::table('profiles')->where('id', $user->id)->first();
                $suspended = (bool) ($profile->suspended ?? false);
            }
            $hasStaffRole = collect($roles)->contains(fn ($r) => $r !== 'customer');
            $isStaff = $hasStaffRole && $internalDomain && ! $suspended;
            $isAdmin = in_array('system_admin', $roles, true) && $internalDomain && ! $suspended;
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'full_name' => $profile->full_name ?? $user->name,
                    'email' => $user->email,
                ] : null,
                'roles' => $roles,
                'isStaff' => $isStaff,
                'isAdmin' => $isAdmin,
                'badges' => $this->badges($user, $isStaff),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'setup_link' => fn () => $request->session()->get('setup_link'),
            ],
        ];
    }

    /** Sidebar unread badges. Defensive: only queries tables that exist. */
    private function badges($user, bool $isStaff): array
    {
        if (! $user) {
            return [];
        }
        $b = [];
        try {
            if (Schema::hasTable('notifications')) {
                $b['notifications'] = DB::table('notifications')
                    ->where('user_id', $user->id)->whereNull('read_at')->count();
            }
            if (Schema::hasTable('requests')) {
                $b['requests'] = $isStaff
                    ? DB::table('requests')->whereNotIn('status', ['closed', 'rejected', 'cancelled'])->count()
                    : DB::table('requests')->where('customer_id', $user->id)
                        ->whereNotIn('status', ['closed', 'rejected', 'cancelled'])->count();
            }
        } catch (\Throwable $e) {
            // ignore during migration/setup
        }

        return $b;
    }
}
