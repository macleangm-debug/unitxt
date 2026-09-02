<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessInvite;
use App\Models\User;
use App\Services\AffiliateService;
use App\Services\ReferralService;
use App\Support\AffiliateProgram;
use App\Support\Countries;
use App\Support\Plans;
use App\Support\Sectors;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class BusinessRegisterController extends Controller
{
    public function create(Request $request, ReferralService $referrals, AffiliateService $affiliates): View
    {
        $ref = $request->query('ref');
        $affiliate = $affiliates->findByPromo($ref);
        $referrer = $affiliate ? null : $referrals->findReferrer($ref);

        if (filled($ref) && AffiliateProgram::isEnabled()) {
            $minutes = max(1, (int) AffiliateProgram::settings()['cookie_days']) * 24 * 60;
            Cookie::queue('loop_ref', strtoupper(trim((string) $ref)), $minutes);
        }

        $scoutId = $request->query('scout');
        if (filled($scoutId) && ctype_digit((string) $scoutId)) {
            $request->session()->put('scout_customer_id', (int) $scoutId);
        }

        return view('auth.business-register', [
            'sectors' => Sectors::all(),
            'countries' => Countries::enabledOptions(),
            'preferredCountry' => session('preferred_country', 'TZ'),
            'referralCode' => $affiliate?->promo_code ?? $referrer?->referral_code ?? old('referral_code', $ref),
            'referrerBusiness' => $referrer,
            'referrerAffiliate' => $affiliate,
            'affiliateDiscount' => $affiliate ? AffiliateProgram::referredDiscountPercent() : null,
            'scoutCustomerId' => $request->session()->get('scout_customer_id'),
        ]);
    }

    public function store(Request $request, ReferralService $referrals, AffiliateService $affiliates): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'country' => ['required', Countries::enabledRule()],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'business_name' => ['required', 'string', 'max:120'],
            'sector' => ['required', 'in:'.implode(',', array_keys(Sectors::all()))],
            'sector_other' => ['nullable', 'required_if:sector,other', 'string', 'max:80'],
            'hotline_country_code' => ['nullable', 'string', 'max:8'],
            'hotline' => ['nullable', 'string', 'max:40'],
            'referral_code' => ['nullable', 'string', 'max:16'],
        ]);

        $countryCode = Countries::dial($data['country']);
        $phone = Countries::normalizePhone($data['phone']);
        $hotline = null;
        if (! blank($data['hotline'] ?? null)) {
            $hotline = trim(($data['hotline_country_code'] ?? $countryCode).' '.Countries::normalizePhone($data['hotline']));
        }

        if (User::query()->where('country_code', $countryCode)->where('phone', $phone)->exists()) {
            return back()->withInput()->withErrors([
                'phone' => __('This phone number is already on Loop.'),
            ]);
        }

        $owner = DB::transaction(function () use ($data, $countryCode, $phone, $hotline, $referrals, $affiliates, $request) {
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
                'hotline' => $hotline,
                'plan_key' => Plans::FREE,
                'billing_status' => 'trialing',
                'trial_ends_at' => now()->addDays(Plans::trialDays()),
            ]);

            $owner->update(['business_id' => $business->id]);

            $code = $data['referral_code'] ?? $request->cookie('loop_ref');
            if (! $affiliates->attachToBusiness($business, $code)) {
                $referrals->attachReferral($business, $code);
            }

            if ($data['sector'] === 'other' && filled($data['sector_other'] ?? null)) {
                \App\Models\SectorSearchMiss::record($data['sector_other']);
            }

            return $owner;
        });

        $scoutId = $request->session()->pull('scout_customer_id');
        if ($scoutId) {
            BusinessInvite::query()
                ->where('customer_id', $scoutId)
                ->where('status', 'pending')
                ->whereRaw('LOWER(business_name) = ?', [Str::lower($data['business_name'])])
                ->update(['status' => 'converted']);
        }

        event(new Registered($owner));
        Auth::login($owner);
        $request->session()->put('preferred_country', $data['country']);
        $owner->marketing_opt_in = $request->boolean('marketing_opt_in');
        $owner->save();
        app(\App\Services\LegalService::class)->recordSignup($owner, 'business');

        return redirect()->route('onboarding.show');
    }
}
