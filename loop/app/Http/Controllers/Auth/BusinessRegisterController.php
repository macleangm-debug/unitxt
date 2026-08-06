<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Shop;
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
            'city' => ['nullable', 'string', 'max:80'],
            'shop_name' => ['required', 'string', 'max:120'],
        ]);

        $countryCode = Countries::dial($data['country']);
        $phone = Countries::normalizePhone($data['phone']);

        if (User::query()->where('country_code', $countryCode)->where('phone', $phone)->exists()) {
            return back()->withInput()->withErrors([
                'phone' => 'This phone number is already on Loop.',
            ]);
        }

        $owner = DB::transaction(function () use ($data, $countryCode, $phone) {
            $owner = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'country_code' => $countryCode,
                'phone' => $phone,
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => User::ROLE_OWNER,
                'phone_verified_at' => now(),
                'is_active' => true,
            ]);

            $business = Business::create([
                'owner_id' => $owner->id,
                'name' => $data['business_name'],
                'slug' => Str::slug($data['business_name']).'-'.Str::lower(Str::random(4)),
                'sector' => $data['sector'],
                'country' => $data['country'],
                'currency' => Countries::currency($data['country']),
                'city' => $data['city'] ?? null,
            ]);

            $owner->update(['business_id' => $business->id]);

            Shop::create([
                'business_id' => $business->id,
                'name' => $data['shop_name'],
                'city' => $data['city'] ?? null,
                'is_active' => true,
            ]);

            return $owner;
        });

        event(new Registered($owner));
        Auth::login($owner);

        return redirect()->route('dashboard')->with('status', 'Welcome to Loop. Add a campaign, then use the till when customers visit.');
    }
}
