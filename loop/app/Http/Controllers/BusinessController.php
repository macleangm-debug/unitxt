<?php

namespace App\Http\Controllers;

use App\Support\Confirm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function edit(Request $request): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        return view('business.edit', [
            'business' => $business,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:80'],
            'hotline' => ['nullable', 'string', 'max:40'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'allow_pay_with_points' => ['sometimes', 'boolean'],
            'pay_spend_step' => ['nullable', 'integer', 'min:1'],
            'pay_points_per_step' => ['nullable', 'integer', 'min:1'],
            'pay_points_max_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($request->hasFile('logo')) {
            $business->logo_path = $request->file('logo')->store('business-logos', 'public');
        }

        $allowPay = $request->boolean('allow_pay_with_points');

        $business->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'city' => $data['city'] ?? $business->city,
            'hotline' => $data['hotline'] ?? null,
            'logo_path' => $business->logo_path,
            'is_active' => $request->boolean('is_active', $business->is_active),
            'allow_pay_with_points' => $allowPay,
            'pay_spend_step' => $allowPay ? ($data['pay_spend_step'] ?? $business->pay_spend_step) : null,
            'pay_points_per_step' => $allowPay ? ($data['pay_points_per_step'] ?? $business->pay_points_per_step) : null,
            'pay_points_max_percent' => $allowPay ? ($data['pay_points_max_percent'] ?? 50) : ($business->pay_points_max_percent ?: 50),
        ]);

        return redirect()->route('business.edit')->with('confirm', Confirm::make(
            __('loop.business_updated_title'),
            __('loop.business_updated_body'),
            __('loop.done'),
            route('settings'),
            false,
        ));
    }
}
