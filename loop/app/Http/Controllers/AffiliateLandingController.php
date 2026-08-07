<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
use App\Support\AffiliateProgram;
use App\Support\Confirm;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateLandingController extends Controller
{
    public function index(): View
    {
        $settings = AffiliateProgram::settings();
        $example = AffiliateProgram::commissionOn(60000);

        return view('affiliates.landing', [
            'settings' => $settings,
            'example' => $example,
            'enabled' => AffiliateProgram::isEnabled(),
        ]);
    }

    public function applyForm(): View|RedirectResponse
    {
        if (! AffiliateProgram::isEnabled()) {
            return redirect()->route('affiliates.landing');
        }

        return view('affiliates.apply', [
            'countries' => Countries::OPTIONS,
            'preferredCountry' => session('preferred_country', 'TZ'),
            'idTypes' => [
                'national_id' => __('loop.id_national'),
                'passport' => __('loop.id_passport'),
                'drivers_license' => __('loop.id_drivers'),
                'voter_id' => __('loop.id_voter'),
            ],
            'settings' => AffiliateProgram::settings(),
        ]);
    }

    public function apply(Request $request, AffiliateService $affiliates): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'country' => ['required', 'in:'.implode(',', array_keys(Countries::OPTIONS))],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'id_type' => ['required', 'in:national_id,passport,drivers_license,voter_id'],
            'id_number' => ['required', 'string', 'max:64'],
            'city' => ['required', 'string', 'max:80'],
            'district' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'max:255'],
        ]);

        $allowedCities = Countries::cities($data['country']);
        if ($allowedCities && ! in_array($data['city'], $allowedCities, true)) {
            return back()->withInput()->withErrors(['city' => __('loop.invalid_city')]);
        }

        $countryCode = Countries::dial($data['country']);
        $phone = Countries::normalizePhone($data['phone']);

        $affiliates->apply([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'country' => $data['country'],
            'country_code' => $countryCode,
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'id_type' => $data['id_type'],
            'id_number' => $data['id_number'],
            'city' => $data['city'],
            'district' => $data['district'],
            'address' => $data['address'],
        ]);

        return redirect()->route('affiliates.status')->with('confirm', Confirm::make(
            __('loop.affiliate_applied_title'),
            __('loop.affiliate_applied_body'),
            __('loop.check_status'),
            route('affiliates.status'),
            false,
        ));
    }

    public function statusForm(): View
    {
        return view('affiliates.status', [
            'countries' => Countries::OPTIONS,
            'preferredCountry' => session('preferred_country', 'TZ'),
            'affiliate' => null,
        ]);
    }

    public function statusLookup(Request $request, AffiliateService $affiliates): View
    {
        $data = $request->validate([
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $phone = Countries::normalizePhone($data['phone']);
        $affiliate = $affiliates->findByPhone($data['country_code'], $phone);

        return view('affiliates.status', [
            'countries' => Countries::OPTIONS,
            'preferredCountry' => session('preferred_country', 'TZ'),
            'affiliate' => $affiliate,
            'lookedUp' => true,
            'lookupPhone' => $data['country_code'].' '.$phone,
        ]);
    }
}
