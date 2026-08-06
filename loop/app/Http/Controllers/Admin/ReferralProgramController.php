<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Support\ReferralProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralProgramController extends Controller
{
    public function edit(): View
    {
        return view('admin.referrals.program', [
            'program' => ReferralProgram::settings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'goal_count' => ['required', 'integer', 'min:1', 'max:50'],
            'referrer_months_per_referral' => ['required', 'integer', 'min:0', 'max:12'],
            'referrer_discount_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'referred_extra_trial_days' => ['required', 'integer', 'min:0', 'max:180'],
            'referred_bonus_months' => ['required', 'integer', 'min:0', 'max:12'],
            'milestone_count' => ['nullable', 'array'],
            'milestone_count.*' => ['nullable', 'integer', 'min:1', 'max:100'],
            'milestone_bonus' => ['nullable', 'array'],
            'milestone_bonus.*' => ['nullable', 'integer', 'min:0', 'max:24'],
        ]);

        PlatformSetting::putValue(
            ReferralProgram::KEY,
            ReferralProgram::normalizeInput($data)
        );

        return back()->with('status', __('loop.admin_referral_program_saved'));
    }
}
