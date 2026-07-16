<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Profile;
use App\Models\SystemSetting;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $profile = Profile::query()->find($user->id);

        // Option lists sourced from system_settings (regions/cities/business fields).
        $optionKeys = ['customer_regions', 'customer_cities', 'customer_business_fields'];
        $options = SystemSetting::query()
            ->whereIn('key', $optionKeys)
            ->pluck('value', 'key');

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'profile' => $profile ? [
                'full_name' => $profile->full_name,
                'phone' => $profile->phone,
                'city' => $profile->city,
                'region' => $profile->region,
                'business_field' => $profile->business_field,
                'account_number' => $profile->account_number,
                'account_type' => $profile->account_type,
            ] : null,
            'options' => [
                'regions' => $options['customer_regions'] ?? [],
                'cities' => $options['customer_cities'] ?? (object) [],
                'business_fields' => $options['customer_business_fields'] ?? [],
            ],
        ]);
    }

    /**
     * Update the user's profile information (account + profiles row).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->only('name', 'email'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Persist the extended profiles fields.
        $profileData = $request->safe()->only(['full_name', 'phone', 'city', 'region', 'business_field']);
        if (! empty($profileData)) {
            Profile::query()->updateOrCreate(
                ['id' => $user->id],
                array_merge($profileData, ['email' => $user->email]),
            );
        }

        return Redirect::route('profile.edit')->with('success', 'تم حفظ التغييرات.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
