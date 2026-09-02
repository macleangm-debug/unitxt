<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Confirm;
use App\Support\Countries;
use App\Support\Sectors;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function create(): View
    {
        return view('auth.customer-phone', [
            'countries' => Countries::authOptions(),
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
            ->where('country_code', $data['country_code'])
            ->where('phone', $phone)
            ->first();

        if ($user && $user->isAdmin()) {
            return back()->with('confirm', Confirm::make(
                __('loop.phone_already_on_loop'),
                __('loop.phone_belongs_to_staff'),
                __('loop.try_again'),
                route('customer.login'),
                false,
                ['kind' => 'error', 'dismiss' => true],
            ));
        }

        if ($user && $user->isStaff()) {
            return back()->with('confirm', Confirm::make(
                __('loop.staff_is_member_title'),
                __('loop.staff_is_member_body'),
                __('loop.done'),
                route('customer.login'),
                false,
                ['dismiss' => true],
            ));
        }

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
            return back()->with('confirm', Confirm::make(
                __('loop.login_failed_title'),
                __('loop.pin_incorrect'),
                __('loop.try_again'),
                route('customer.pin'),
                false,
            ));
        }

        if ($user->profile_completed) {
            Auth::login($user);
            $request->session()->forget('customer_auth');
            $request->session()->regenerate();

            return redirect()->route('dashboard');
        }

        $auth['pin_verified'] = true;
        $request->session()->put('customer_auth', $auth);

        return redirect()->route('customer.register');
    }

    public function registerForm(Request $request): View|RedirectResponse
    {
        $auth = $request->session()->get('customer_auth');
        if (! $auth) {
            return redirect()->route('customer.login');
        }

        $existing = $this->pendingCustomer($auth);
        if ($existing && $existing->isStaff()) {
            return redirect()->route('customer.login')->with('confirm', Confirm::make(
                __('loop.staff_is_member_title'),
                __('loop.staff_is_member_body'),
                __('loop.done'),
                route('customer.login'),
                false,
                ['dismiss' => true],
            ));
        }
        if ($existing && ! $existing->isCustomer()) {
            return redirect()->route('customer.login')->with('confirm', Confirm::make(
                __('loop.phone_already_on_loop'),
                __('loop.phone_belongs_to_staff'),
                __('loop.try_again'),
                route('customer.login'),
                false,
                ['kind' => 'error', 'dismiss' => true],
            ));
        }

        $country = $existing?->country ?: ($auth['country'] ?? 'TZ');

        return view('auth.customer-register', [
            'auth' => $auth,
            'existing' => $existing,
            'countries' => Countries::authOptions(),
            'country' => $country,
            'cities' => Countries::cities($country),
            'sectors' => Sectors::all(),
            'needsPin' => empty($auth['has_pin']) && empty($auth['pin_verified']) && ! $existing?->password,
            'knownName' => (bool) $existing?->hasKnownName(),
            'knownBirthday' => (bool) $existing?->hasBirthday(),
            'knownGender' => (bool) $existing?->hasGender(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $auth = $request->session()->get('customer_auth');
        if (! $auth) {
            return redirect()->route('customer.login');
        }

        $user = $this->pendingCustomer($auth);
        if ($user && $user->isStaff()) {
            return redirect()->route('customer.login')->with('confirm', Confirm::make(
                __('loop.staff_is_member_title'),
                __('loop.staff_is_member_body'),
                __('loop.done'),
                route('customer.login'),
                false,
                ['dismiss' => true],
            ));
        }
        if ($user && ! $user->isCustomer()) {
            return redirect()->route('customer.login')->with('confirm', Confirm::make(
                __('loop.phone_already_on_loop'),
                __('loop.phone_belongs_to_staff'),
                __('loop.try_again'),
                route('customer.login'),
                false,
                ['kind' => 'error', 'dismiss' => true],
            ));
        }

        $needsPin = empty($auth['has_pin']) && empty($auth['pin_verified']) && ! $user?->password;

        $data = $request->validate([
            'first_name' => [Rule::requiredIf(! $user?->hasKnownName()), 'nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'country' => ['required', Countries::authRule()],
            'city' => ['required', 'string', 'max:80'],
            'birth_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'birth_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'gender' => ['nullable', 'in:male,female'],
            'email' => ['nullable', 'email', 'max:255'],
            'interests' => ['nullable', 'array'],
            'interests.*' => ['in:'.implode(',', array_keys(Sectors::all()))],
            'pin' => [$needsPin ? 'required' : 'nullable', 'digits_between:4,6', 'confirmed'],
        ]);

        $payload = [
            'first_name' => $data['first_name'] ?? $user?->first_name,
            'last_name' => $data['last_name'] ?? null,
            'country' => $data['country'],
            'city' => $data['city'],
            'birth_month' => $data['birth_month'] ?? null,
            'birth_day' => $data['birth_day'] ?? null,
            'gender' => $data['gender'] ?? null,
            'email' => $data['email'] ?? null,
            'interests' => $data['interests'] ?? [],
        ];

        if ($user) {
            $user->mergeMemberProfile($payload);
            if ($needsPin && ! empty($data['pin'])) {
                $user->password = $data['pin'];
            }
            $user->phone_verified_at = $user->phone_verified_at ?? now();
            $user->profile_completed = true;
            $user->is_active = true;
            $user->save();
        } else {
            try {
                $user = User::create([
                    'first_name' => $payload['first_name'],
                    'last_name' => $payload['last_name'] ?? '',
                    'country' => $payload['country'],
                    'city' => $payload['city'],
                    'birth_month' => $payload['birth_month'],
                    'birth_day' => $payload['birth_day'],
                    'gender' => $payload['gender'],
                    'email' => $payload['email'],
                    'interests' => $payload['interests'] ?: [],
                    'password' => $data['pin'],
                    'phone_verified_at' => now(),
                    'profile_completed' => true,
                    'is_active' => true,
                    'country_code' => $auth['country_code'],
                    'phone' => $auth['phone'],
                    'role' => User::ROLE_CUSTOMER,
                ]);
            } catch (UniqueConstraintViolationException) {
                return redirect()->route('customer.login')->withErrors([
                    'phone' => __('loop.phone_already_on_loop'),
                ]);
            }
        }

        Auth::login($user);
        $request->session()->forget('customer_auth');
        $request->session()->regenerate();
        $request->session()->put('preferred_country', $data['country']);
        $request->session()->flash('show_welcome', true);

        $user->marketing_opt_in = $request->boolean('marketing_opt_in');
        $user->save();
        app(\App\Services\LegalService::class)->recordSignup($user, 'member');

        return redirect()->route('dashboard');
    }

    /**
     * @param  array{country_code: string, phone: string}  $auth
     */
    private function pendingCustomer(array $auth): ?User
    {
        return User::query()
            ->where('country_code', $auth['country_code'])
            ->where('phone', $auth['phone'])
            ->first();
    }
}
