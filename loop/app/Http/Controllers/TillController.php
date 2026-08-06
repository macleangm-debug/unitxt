<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TillService;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TillController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->workplace();

        return view('till.index', [
            'business' => $business,
            'shops' => $business?->shops()->where('is_active', true)->orderBy('name')->get() ?? collect(),
            'countries' => Countries::OPTIONS,
            'recent' => $business
                ? $business->visits()->with(['customer', 'shop', 'recorder'])->latest()->take(8)->get()
                : collect(),
        ]);
    }

    public function lookup(Request $request, TillService $till): View|RedirectResponse
    {
        $data = $request->validate([
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
            'shop_id' => ['required', 'exists:shops,id'],
            'channel' => ['required', 'in:in_store,phone_order'],
        ]);

        $business = $request->user()->workplace();
        abort_unless($business, 403);

        $shop = $business->shops()->whereKey($data['shop_id'])->firstOrFail();
        $phone = Countries::normalizePhone($data['phone']);
        $customer = $till->findCustomer($data['country_code'], $phone);

        $campaign = $business->campaigns()
            ->active()
            ->whereIn('type', ['earn', 'product_push'])
            ->where(function ($query) use ($shop) {
                $query->whereDoesntHave('shops')
                    ->orWhereHas('shops', fn ($shops) => $shops->where('shops.id', $shop->id));
            })
            ->orderByDesc('points_per_step')
            ->first();

        return view('till.sale', [
            'business' => $business,
            'shop' => $shop,
            'channel' => $data['channel'],
            'country_code' => $data['country_code'],
            'phone' => $phone,
            'customer' => $customer,
            'membership' => $customer
                ? $business->memberships()
                    ->where('customer_id', $customer->id)
                    ->where('shop_id', $shop->id)
                    ->first()
                : null,
            'rewards' => $business->rewards()->where('is_active', true)->orderBy('points_cost')->get(),
            'campaign' => $campaign,
        ]);
    }

    public function store(Request $request, TillService $till): RedirectResponse
    {
        $business = $request->user()->workplace();
        abort_unless($business && $request->user()->canUseTill(), 403);

        $amountRaw = str_replace([',', ' '], '', (string) $request->input('amount_spent', '0'));
        $request->merge(['amount_spent' => $amountRaw]);

        $data = $request->validate([
            'shop_id' => ['required', 'exists:shops,id'],
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
            'channel' => ['required', 'in:in_store,phone_order'],
            'amount_spent' => ['required', 'numeric', 'min:0'],
            'reward_id' => ['nullable', 'exists:rewards,id'],
            'receipt_ref' => ['nullable', 'string', 'max:80'],
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'birth_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'birth_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'email' => ['nullable', 'email', 'max:255'],
            'pay_with_points' => ['nullable', 'boolean'],
            'points_to_spend' => ['nullable', 'integer', 'min:1'],
        ]);

        $shop = $business->shops()->whereKey($data['shop_id'])->firstOrFail();
        $phone = Countries::normalizePhone($data['phone']);
        $customer = $till->findCustomer($data['country_code'], $phone);
        $payWithPoints = $request->boolean('pay_with_points');

        if ((float) $data['amount_spent'] <= 0 && ! $payWithPoints) {
            return back()->withErrors(['amount_spent' => __('loop.amount_required')])->withInput();
        }

        if (! $customer) {
            $request->validate([
                'first_name' => ['required', 'string', 'max:80'],
                'last_name' => ['required', 'string', 'max:80'],
            ]);

            $customer = $till->registerCustomer([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'country_code' => $data['country_code'],
                'phone' => $phone,
                'email' => $data['email'] ?? null,
                'birth_month' => $data['birth_month'] ?? null,
                'birth_day' => $data['birth_day'] ?? null,
            ]);
        }

        $visit = $till->recordSale(
            $request->user(),
            $shop,
            $customer,
            (float) $data['amount_spent'],
            $data['reward_id'] ?? null,
            $data['receipt_ref'] ?? null,
            $data['channel'],
            $payWithPoints,
            $payWithPoints ? ($data['points_to_spend'] ?? null) : null,
        );

        $message = "{$visit->customer->name}: +{$visit->points_earned} pts";
        if ($visit->points_redeemed > 0) {
            $message .= " · −{$visit->points_redeemed} pts";
            if ($visit->discount_amount > 0) {
                $message .= " ({$business->currency} ".number_format((float) $visit->discount_amount, 0).' off)';
            }
        }

        return redirect()->route('till.index')->with('status', $message);
    }
}
