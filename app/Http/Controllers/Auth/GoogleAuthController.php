<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\IdentityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(private IdentityService $identity) {}

    public function redirect()
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('login')->with('error', 'لم يتم تفعيل الدخول عبر Google بعد.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', 'تعذّر تسجيل الدخول عبر Google.');
        }

        $email = strtolower($google->getEmail());
        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $google->getName() ?: $email,
                'email' => $email,
                'password' => bcrypt(Str::random(40)),
                'avatar' => $google->getAvatar(),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $this->identity->provision($user, ['full_name' => $google->getName()]);
        } else {
            $this->identity->provision($user);
        }

        $user->load('profile', 'roles');

        // Staff accounts must use the internal domain and not be suspended.
        if ($user->hasStaffRole() && (! $user->isInternalEmail() || $user->isSuspended())) {
            $reason = $user->isSuspended() ? 'suspended' : 'external_domain';
            $this->identity->logAccess($user->id, $email, 'google', 'denied', $reason);

            return redirect()->route('login')->with('error', 'تم رفض الدخول: الحساب غير مصرّح له.');
        }

        $this->identity->logAccess($user->id, $email, 'google', 'allowed');
        Auth::login($user, true);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
