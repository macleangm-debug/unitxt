<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Support\Confirm;
use App\Support\Countries;
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
            'shops' => $business->shops()->with('business')->latest()->get(),
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
            'cities' => Countries::cities($business->country),
            'countries' => Countries::formOptions($business->country),
            'defaultDial' => Countries::dial($business->country),
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
            'country_code' => ['nullable', 'string', 'max:8'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $phone = $this->formatPhone($data['country_code'] ?? Countries::dial($business->country), $data['phone'] ?? null);

        $shop = $business->shops()->create([
            'name' => $data['name'],
            'address' => $data['address'],
            'city' => $data['city'],
            'phone' => $phone,
            'logo_path' => null,
            'code' => 'SHOP-'.Str::upper(Str::random(6)),
            'is_active' => true,
        ]);

        return redirect()->route('shops.show', $shop)->with('confirm', Confirm::make(
            __('loop.shop_added_title'),
            __('loop.shop_added_body', ['name' => $shop->name]),
            __('loop.view_shop'),
            route('shops.show', $shop),
        ));
    }

    public function show(Request $request, Shop $shop): View
    {
        $this->authorizeShop($request, $shop);

        return view('shops.show', [
            'shop' => $shop,
            'business' => $shop->business,
        ]);
    }

    public function edit(Request $request, Shop $shop): View
    {
        $this->authorizeShop($request, $shop);
        [$dial, $local] = $this->splitPhone($shop->phone, $shop->business->country);

        return view('shops.edit', [
            'shop' => $shop,
            'business' => $shop->business,
            'cities' => Countries::cities($shop->business->country),
            'countries' => Countries::formOptions($shop->business->country),
            'dial' => $dial,
            'localPhone' => $local,
        ]);
    }

    public function update(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeShop($request, $shop);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:80'],
            'country_code' => ['nullable', 'string', 'max:8'],
            'phone' => ['nullable', 'string', 'max:40'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $phone = $this->formatPhone(
            $data['country_code'] ?? Countries::dial($shop->business->country),
            $data['phone'] ?? null
        );

        $shop->update([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'city' => $data['city'],
            'phone' => $phone,
            'is_active' => $request->boolean('is_active', $shop->is_active),
        ]);

        return redirect()->route('shops.show', $shop)->with('confirm', Confirm::make(
            __('loop.shop_updated_title'),
            __('loop.shop_updated_body', ['name' => $shop->name]),
            __('loop.view_shop'),
            route('shops.show', $shop),
            false,
        ));
    }

    public function destroy(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeShop($request, $shop);
        $shop->delete();

        return redirect()->route('shops.index')->with('confirm', Confirm::make(
            __('loop.shop_removed_title'),
            __('loop.shop_removed_body'),
            __('loop.back_to_shops'),
            route('shops.index'),
            false,
        ));
    }

    private function authorizeShop(Request $request, Shop $shop): void
    {
        abort_unless($request->user()->ownedBusiness?->id === $shop->business_id, 403);
    }

    private function formatPhone(?string $dial, ?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $local = Countries::normalizePhone($phone);
        $dial = $dial ?: '+255';

        return trim($dial.' '.$local);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitPhone(?string $phone, string $country): array
    {
        $defaultDial = Countries::dial($country);
        if (blank($phone)) {
            return [$defaultDial, ''];
        }

        foreach (Countries::OPTIONS as $meta) {
            $dial = $meta['dial'];
            if (str_starts_with($phone, $dial)) {
                return [$dial, trim(substr($phone, strlen($dial)))];
            }
        }

        return [$defaultDial, Countries::normalizePhone($phone)];
    }
}
