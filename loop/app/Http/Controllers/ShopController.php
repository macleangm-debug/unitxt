<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business, 403);

        return view('shops.index', [
            'business' => $business,
            'shops' => $business->shops()->latest()->get(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();
        $limits = app(\App\Services\PlanLimitService::class);

        if (! $limits->canAddShop($business)) {
            return redirect()
                ->route('shops.index')
                ->withErrors(['plan' => $limits->shopLimitMessage($business)]);
        }

        return view('shops.create', [
            'business' => $business,
            'cities' => \App\Support\Countries::cities($business->country),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();
        $limits = app(\App\Services\PlanLimitService::class);

        if (! $limits->canAddShop($business)) {
            return redirect()
                ->route('shops.index')
                ->withErrors(['plan' => $limits->shopLimitMessage($business)]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('shop-logos', 'public');
        }

        $business->shops()->create([
            'name' => $data['name'],
            'address' => $data['address'],
            'city' => $data['city'],
            'phone' => $data['phone'] ?? null,
            'logo_path' => $logoPath,
            'code' => 'SHOP-'.Str::upper(Str::random(6)),
            'is_active' => true,
        ]);

        return redirect()->route('shops.index')->with('status', 'Shop added.');
    }

    public function edit(Request $request, Shop $shop): View
    {
        $this->authorizeShop($request, $shop);

        return view('shops.edit', [
            'shop' => $shop,
            'business' => $shop->business,
            'cities' => \App\Support\Countries::cities($shop->business->country),
        ]);
    }

    public function update(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeShop($request, $shop);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($request->hasFile('logo')) {
            $shop->logo_path = $request->file('logo')->store('shop-logos', 'public');
        }

        $shop->update([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'city' => $data['city'],
            'phone' => $data['phone'] ?? null,
            'logo_path' => $shop->logo_path,
            'is_active' => $request->boolean('is_active', $shop->is_active),
        ]);

        return redirect()->route('shops.index')->with('status', 'Shop updated.');
    }

    public function destroy(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeShop($request, $shop);
        $shop->delete();

        return redirect()->route('shops.index')->with('status', 'Shop removed.');
    }

    private function authorizeShop(Request $request, Shop $shop): void
    {
        abort_unless($request->user()->ownedBusiness?->id === $shop->business_id, 403);
    }
}
