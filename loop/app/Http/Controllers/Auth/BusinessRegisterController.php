<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use App\Support\Countries;
use App\Support\Sectors;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class BusinessRegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.business-register', [
            'sectors' => Sectors::OPTIONS,
            'countries' => Countries::OPTIONS,
            'preferredCountry' => session('preferred_country', 'TZ'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'country' => ['required', 'in:'.implode(',', array_keys(Countries::OPTIONS))],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'business_name' => ['required', 'string', 'max:120'],
            'sector' => ['required', 'in:'.implode(',', array_keys(Sectors::OPTIONS))],
            'sector_other' => ['nullable', 'required_if:sector,other', 'string', 'max:80'],
        ]);

        $countryCode = Countries::dial($data['country']);
        $phone = Countries::normalizePhone($data['phone']);

        if (User::query()->where('country_code', $countryCode)->where('phone', $phone)->exists()) {
            return back()->withInput()->withErrors([
                'phone' => __('This phone number is already on Loop.'),
            ]);
        }

        $owner = DB::transaction(function () use ($data, $countryCode, $phone) {
            $owner = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'country_code' => $countryCode,
                'country' => $data['country'],
                'phone' => $phone,
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => User::ROLE_OWNER,
                'phone_verified_at' => now(),
                'is_active' => true,
                'profile_completed' => true,
            ]);

            $business = Business::create([
                'owner_id' => $owner->id,
                'name' => $data['business_name'],
                'slug' => Str::slug($data['business_name']).'-'.Str::lower(Str::random(4)),
                'sector' => $data['sector'],
                'sector_other' => $data['sector'] === 'other' ? ($data['sector_other'] ?? null) : null,
                'country' => $data['country'],
                'currency' => Countries::currency($data['country']),
                'city' => null,
            ]);

            $owner->update(['business_id' => $business->id]);

            return $owner;
        });

        event(new Registered($owner));
        Auth::login($owner);
        $request->session()->put('preferred_country', $data['country']);

        return redirect()->route('onboarding.show');
    }
}
