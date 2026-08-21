<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AffiliateService;
use App\Support\AffiliateProgram;
use App\Support\Confirm;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AffiliateAuthController extends Controller
{
    public function loginForm(): View
    {
        return view('auth.affiliate-login', [
            'countries' => Countries::authOptions(),
            'preferredCountry' => session('preferred_country', 'TZ'),
            'pinLength' => AffiliateProgram::pinLength(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $pinLength = AffiliateProgram::pinLength();
        $data = $request->validate([
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
            'pin' => ['required', 'digits:'.$pinLength],
        ]);

        $phone = Countries::normalizePhone($data['phone']);
        $user = User::query()
            ->where('role', User::ROLE_AFFILIATE)
            ->where('country_code', $data['country_code'])
            ->where('phone', $phone)
            ->where('is_active', true)
            ->first();

        $ok = $user && $user->pin_hash && Hash::check($data['pin'], $user->pin_hash);

        if (! $ok) {
            return back()->withInput()->with('confirm', Confirm::make(
                __('loop.login_failed_title'),
                __('loop.affiliate_login_failed'),
                __('loop.try_again'),
                route('affiliate.login'),
                false,
            ));
        }

        Auth::login($user);
        $request->session()->regenerate();

        $affiliate = $user->affiliateProfile;
        if ($affiliate?->needsSetup()) {
            return redirect()->route('affiliate.setup');
        }

        return redirect()->route('affiliate.dashboard')->with('confirm', Confirm::make(
            __('loop.welcome_back'),
            __('loop.affiliate_login_confirm_body', ['code' => $affiliate?->promo_code ?? '']),
            __('loop.start_sharing'),
            route('affiliate.dashboard'),
            false,
        ));
    }

    public function activateForm(Request $request, AffiliateService $affiliates): View|RedirectResponse
    {
        $countryCode = $request->query('country_code');
        $phone = $request->query('phone');
        $affiliate = null;
        if ($countryCode && $phone) {
            $affiliate = $affiliates->findByPhone($countryCode, Countries::normalizePhone($phone));
        }

        return view('auth.affiliate-activate', [
            'countries' => Countries::authOptions(),
            'preferredCountry' => session('preferred_country', 'TZ'),
            'pinLength' => AffiliateProgram::pinLength(),
            'affiliate' => $affiliate?->canActivate() ? $affiliate : null,
            'prefillCountry' => $countryCode,
            'prefillPhone' => $phone ? Countries::normalizePhone($phone) : null,
        ]);
    }

    public function activateLookup(Request $request, AffiliateService $affiliates): RedirectResponse
    {
        $data = $request->validate([
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $phone = Countries::normalizePhone($data['phone']);
        $affiliate = $affiliates->findByPhone($data['country_code'], $phone);

        if (! $affiliate) {
            return back()->withErrors(['phone' => __('loop.affiliate_not_found')]);
        }

        if ($affiliate->status === 'pending') {
            return back()->withErrors(['phone' => __('loop.affiliate_still_pending')]);
        }

        if ($affiliate->status === 'rejected') {
            return back()->withErrors(['phone' => __('loop.affiliate_was_rejected')]);
        }

        if ($affiliate->isActive()) {
            return redirect()->route('affiliate.login')->with('status', __('loop.affiliate_already_active'));
        }

        if (! $affiliate->canActivate()) {
            return back()->withErrors(['phone' => __('loop.affiliate_cannot_activate')]);
        }

        return redirect()->route('affiliate.activate', [
            'country_code' => $data['country_code'],
            'phone' => $phone,
        ]);
    }

    public function activate(Request $request, AffiliateService $affiliates): RedirectResponse
    {
        $pinLength = AffiliateProgram::pinLength();
        $data = $request->validate([
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
            'pin' => ['required', 'digits:'.$pinLength, 'confirmed'],
        ]);

        $phone = Countries::normalizePhone($data['phone']);
        $affiliate = $affiliates->findByPhone($data['country_code'], $phone);

        if (! $affiliate || ! $affiliate->canActivate()) {
            return back()->withErrors(['phone' => __('loop.affiliate_cannot_activate')]);
        }

        $user = $affiliates->activate($affiliate, Str::password(20), $data['pin']);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('affiliate.setup')->with('confirm', Confirm::make(
            __('loop.affiliate_activated_title'),
            __('loop.affiliate_activated_setup_body'),
            __('loop.choose_promo_code'),
            route('affiliate.setup'),
        ));
    }
}
