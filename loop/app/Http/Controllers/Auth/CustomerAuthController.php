<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        ]);

        return redirect()
            ->route('customer.otp')
            ->with('status', app()->environment('local', 'testing')
                ? 'Demo code: 123456'
                : 'We sent a code to your phone.');
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

        $user = $otp->verify($auth['country_code'], $auth['phone'], $data['code']);

        Auth::login($user);
        $request->session()->forget('customer_auth');
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
