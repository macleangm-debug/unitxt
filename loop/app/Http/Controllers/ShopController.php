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

    public function create(Request $request): View
    {
        return view('shops.create', [
            'business' => $request->user()->ownedBusiness()->firstOrFail(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $business->shops()->create([
            ...$data,
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
        ]);
    }

    public function update(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeShop($request, $shop);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $shop->update([
            ...$data,
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
