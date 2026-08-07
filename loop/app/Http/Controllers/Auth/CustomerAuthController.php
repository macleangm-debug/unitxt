<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Countries;
use App\Support\Sectors;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function create(): View
    {
        return view('auth.customer-phone', [
            'countries' => Countries::OPTIONS,
            'preferredCountry' => session('preferred_country', 'TZ'),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $phone = Countries::normalizePhone($data['phone']);
        $user = User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->where('country_code', $data['country_code'])
            ->where('phone', $phone)
            ->first();

        $request->session()->put('customer_auth', [
            'country_code' => $data['country_code'],
            'phone' => $phone,
            'country' => Countries::fromDial($data['country_code']) ?? 'TZ',
            'exists' => (bool) $user,
            'has_pin' => (bool) ($user?->password),
            'profile_completed' => (bool) ($user?->profile_completed),
        ]);

        if ($user && $user->password) {
            return redirect()->route('customer.pin');
        }

        return redirect()->route('customer.register');
    }

    public function pinForm(Request $request): View|RedirectResponse
    {
        $auth = $request->session()->get('customer_auth');
        if (! $auth || empty($auth['has_pin'])) {
            return redirect()->route('customer.login');
        }

        return view('auth.customer-pin', ['auth' => $auth]);
    }

    public function pinVerify(Request $request): RedirectResponse
    {
        $auth = $request->session()->get('customer_auth');
        if (! $auth) {
            return redirect()->route('customer.login');
        }

        $data = $request->validate([
            'pin' => ['required', 'digits_between:4,6'],
        ]);

        $user = User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->where('country_code', $auth['country_code'])
            ->where('phone', $auth['phone'])
            ->first();

        if (! $user || ! $user->password || ! Hash::check($data['pin'], $user->password)) {
            return back()->withErrors(['pin' => __('loop.pin_incorrect')]);
        }

        Auth::login($user);
        $request->session()->forget('customer_auth');
        $request->session()->regenerate();

        return redirect()->route($user->profile_completed ? 'dashboard' : 'customer.complete');
    }

    public function registerForm(Request $request): View|RedirectResponse
    {
        $auth = $request->session()->get('customer_auth');
        if (! $auth) {
            return redirect()->route('customer.login');
        }

        $country = $auth['country'] ?? 'TZ';
        $existing = User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->where('country_code', $auth['country_code'])
            ->where('phone', $auth['phone'])
            ->first();

        return view('auth.customer-register', [
            'auth' => $auth,
            'existing' => $existing,
            'countries' => Countries::OPTIONS,
            'country' => $country,
            'cities' => Countries::cities($country),
            'sectors' => Sectors::all(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $auth = $request->session()->get('customer_auth');
        if (! $auth) {
            return redirect()->route('customer.login');
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'country' => ['required', 'in:'.implode(',', array_keys(Countries::OPTIONS))],
            'city' => ['required', 'string', 'max:80'],
            'birth_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'birth_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'email' => ['nullable', 'email', 'max:255'],
            'interests' => ['nullable', 'array'],
            'interests.*' => ['in:'.implode(',', array_keys(Sectors::all()))],
            'pin' => ['required', 'digits_between:4,6', 'confirmed'],
        ]);

        $user = User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->where('country_code', $auth['country_code'])
            ->where('phone', $auth['phone'])
            ->first();

        $payload = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'country' => $data['country'],
            'city' => $data['city'],
            'birth_month' => $data['birth_month'] ?? null,
            'birth_day' => $data['birth_day'] ?? null,
            'email' => $data['email'] ?? null,
            'interests' => $data['interests'] ?? [],
            'password' => Hash::make($data['pin']),
            'phone_verified_at' => now(),
            'profile_completed' => true,
            'is_active' => true,
        ];

        if ($user) {
            $user->update($payload);
        } else {
            $user = User::create([
                ...$payload,
                'country_code' => $auth['country_code'],
                'phone' => $auth['phone'],
                'role' => User::ROLE_CUSTOMER,
            ]);
        }

        Auth::login($user);
        $request->session()->forget('customer_auth');
        $request->session()->regenerate();
        $request->session()->put('preferred_country', $data['country']);
        $request->session()->flash('show_welcome', true);

        return redirect()->route('dashboard');
    }
}
