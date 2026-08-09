<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ReferralProgramController extends Controller
{
    /**
     * Legacy page — referral program settings live in the Settings Hub.
     */
    public function edit(): RedirectResponse
    {
        return redirect()->route('admin.settings', ['tab' => 'referrals']);
    }

    /**
     * Legacy endpoint — referral program settings are edited only in the Settings Hub.
     */
    public function update(): RedirectResponse
    {
        return redirect()->route('admin.settings', ['tab' => 'referrals']);
    }
}
