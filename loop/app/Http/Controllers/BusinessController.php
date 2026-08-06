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
            'logo' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($request->hasFile('logo')) {
            $business->logo_path = $request->file('logo')->store('business-logos', 'public');
        }

        $business->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'city' => $data['city'] ?? $business->city,
            'logo_path' => $business->logo_path,
            'is_active' => $request->boolean('is_active', $business->is_active),
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
