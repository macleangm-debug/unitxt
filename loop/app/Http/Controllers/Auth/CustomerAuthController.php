<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Countries;
use App\Support\Sectors;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function create(): View
    {
        return view('auth.customer-phone', [
            'countries' => Countries::OPTIONS,
        ]);
    }

    public function send(Request $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validate([
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $phone = Countries::normalizePhone($data['phone']);
        $otp->send($data['country_code'], $phone);

        $request->session()->put('customer_auth', [
            'country_code' => $data['country_code'],
            'phone' => $phone,
            'country' => Countries::fromDial($data['country_code']) ?? 'TZ',
        ]);

        return redirect()
            ->route('customer.otp')
            ->with('status', app()->environment('local', 'testing')
                ? 'Demo code: 123456'
                : __('We sent a code to your phone.'));
    }

    public function otpForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('customer_auth')) {
            return redirect()->route('customer.login');
        }

        return view('auth.customer-otp', [
            'auth' => $request->session()->get('customer_auth'),
        ]);
    }

    public function verify(Request $request, OtpService $otp): RedirectResponse
    {
        $auth = $request->session()->get('customer_auth');

        if (! $auth) {
            return redirect()->route('customer.login');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:10'],
        ]);

        try {
            $user = $otp->verify($auth['country_code'], $auth['phone'], $data['code']);
        } catch (ValidationException $e) {
            if (($e->errors()['phone'][0] ?? null) === 'complete_profile') {
                $request->session()->put('customer_auth.verified', true);

                return redirect()->route('customer.register');
            }

            throw $e;
        }

        Auth::login($user);
        $request->session()->forget('customer_auth');
        $request->session()->regenerate();

        return redirect()->route('discover');
    }

    public function registerForm(Request $request): View|RedirectResponse
    {
        $auth = $request->session()->get('customer_auth');

        if (! $auth || empty($auth['verified'])) {
            return redirect()->route('customer.login');
        }

        $country = $auth['country'] ?? 'TZ';

        return view('auth.customer-register', [
            'auth' => $auth,
            'countries' => Countries::OPTIONS,
            'country' => $country,
            'cities' => Countries::cities($country),
            'sectors' => Sectors::OPTIONS,
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $auth = $request->session()->get('customer_auth');

        if (! $auth || empty($auth['verified'])) {
            return redirect()->route('customer.login');
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'country' => ['required', 'in:'.implode(',', array_keys(Countries::OPTIONS))],
            'city' => ['required', 'string', 'max:80'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'email' => ['nullable', 'email', 'max:255'],
            'interests' => ['nullable', 'array'],
            'interests.*' => ['in:'.implode(',', array_keys(Sectors::OPTIONS))],
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'country_code' => $auth['country_code'],
            'phone' => $auth['phone'],
            'country' => $data['country'],
            'city' => $data['city'],
            'birth_date' => $data['birth_date'] ?? null,
            'email' => $data['email'] ?? null,
            'interests' => $data['interests'] ?? [],
            'role' => User::ROLE_CUSTOMER,
            'phone_verified_at' => now(),
            'is_active' => true,
        ]);

        Auth::login($user);
        $request->session()->forget('customer_auth');
        $request->session()->regenerate();

        return redirect()->route('discover')->with('status', __('Welcome to Loop. Explore shops in your city.'));
    }
}
