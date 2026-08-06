<?php

namespace App\Http\Controllers;

use App\Models\BusinessInvite;
use App\Support\Confirm;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BusinessInviteController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isCustomer(), 403);

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:120'],
            'country_code' => ['nullable', 'string', 'max:8'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        BusinessInvite::create([
            'customer_id' => $request->user()->id,
            'business_name' => $data['business_name'],
            'country_code' => $data['country_code'] ?? Countries::dial($request->user()->country ?? 'TZ'),
            'phone' => isset($data['phone']) ? Countries::normalizePhone($data['phone']) : null,
            'city' => $data['city'] ?? $request->user()->city,
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('confirm', Confirm::make(
            __('loop.invite_sent_title'),
            __('loop.invite_sent_body', ['name' => $data['business_name']]),
            __('loop.done'),
            route('dashboard'),
        ));
    }
}
