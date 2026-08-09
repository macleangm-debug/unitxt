<?php

namespace App\Http\Controllers;

use App\Services\PlanLimitService;
use App\Services\TillService;
use App\Support\Confirm;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TillController extends Controller
{
    public function index(Request $request, PlanLimitService $limits): View
    {
        $business = $request->user()->workplace();
        if ($business && $request->user()->isOwner()) {
            $limits->syncTrialStatus($business->fresh());
            $business = $business->fresh();
        }

        $tillLocked = $business ? ! $limits->canUseTill($business) : false;
        $isOwner = $request->user()->isOwner();

        return view('till.index', [
            'business' => $business,
            'shops' => $business?->shops()->where('is_active', true)->orderBy('name')->get() ?? collect(),
            'countries' => Countries::OPTIONS,
            'recent' => ($isOwner && $business)
                ? $business->visits()->with(['customer', 'shop', 'recorder'])->latest()->take(8)->get()
                : collect(),
            'showRecent' => $isOwner,
            'tillLocked' => $tillLocked,
            'isOwner' => $isOwner,
        ]);
    }

    public function lookup(Request $request, TillService $till): RedirectResponse
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

        $request->session()->put('till.ticket', [
            'shop_id' => $shop->id,
            'channel' => $data['channel'],
            'country_code' => $data['country_code'],
            'phone' => $phone,
            'customer_id' => $customer?->id,
            'needs_register' => $customer === null,
        ]);

        return redirect()->route('till.ticket');
    }

    public function ticket(Request $request, TillService $till): View|RedirectResponse
    {
        $ticket = $request->session()->get('till.ticket');
        if (! is_array($ticket)) {
            return redirect()->route('till.index');
        }

        $business = $request->user()->workplace();
        abort_unless($business, 403);

        $shop = $business->shops()->whereKey($ticket['shop_id'])->first();
        if (! $shop) {
            $request->session()->forget('till.ticket');

            return redirect()->route('till.index');
        }

        $customer = null;
        if (! empty($ticket['customer_id'])) {
            $customer = $till->findCustomer($ticket['country_code'], $ticket['phone']);
        }

        $campaign = $business->campaigns()
            ->active()
            ->whereIn('type', ['earn', 'product_push'])
            ->where(function ($query) use ($shop) {
                $query->whereDoesntHave('shops')
                    ->orWhereHas('shops', fn ($shops) => $shops->where('shops.id', $shop->id));
            })
            ->orderByDesc('points_per_step')
            ->first();

        $membership = $customer
            ? $business->memberships()
                ->where('customer_id', $customer->id)
                ->where('shop_id', $shop->id)
                ->first()
            : null;

        $rewards = $business->rewards()->where('is_active', true)->orderBy('points_cost')->get();
        $nextOffer = null;
        if ($membership) {
            $nextOffer = $rewards
                ->filter(fn ($r) => $r->points_cost > $membership->points_balance)
                ->sortBy('points_cost')
                ->first();
        }

        return view('till.sale', [
            'business' => $business,
            'shop' => $shop,
            'channel' => $ticket['channel'],
            'country_code' => $ticket['country_code'],
            'phone' => $ticket['phone'],
            'customer' => $customer,
            'membership' => $membership,
            'rewards' => $rewards,
            'campaign' => $campaign,
            'nextOffer' => $nextOffer,
            'needsRegister' => (bool) ($ticket['needs_register'] ?? false) && ! $customer,
            'justRegistered' => (bool) $request->session()->pull('till.just_registered', false),
        ]);
    }

    public function registerCustomer(Request $request, TillService $till): RedirectResponse
    {
        $business = $request->user()->workplace();
        abort_unless($business && $request->user()->canUseTill(), 403);

        $ticket = $request->session()->get('till.ticket');
        abort_unless(is_array($ticket), 403);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'birth_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'birth_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $existing = $till->findCustomer($ticket['country_code'], $ticket['phone']);
        if (! $existing) {
            $existing = $till->registerCustomer([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'country_code' => $ticket['country_code'],
                'phone' => $ticket['phone'],
                'email' => $data['email'] ?? null,
                'birth_month' => $data['birth_month'] ?? null,
                'birth_day' => $data['birth_day'] ?? null,
            ]);
        }

        $ticket['customer_id'] = $existing->id;
        $ticket['needs_register'] = false;
        $request->session()->put('till.ticket', $ticket);
        $request->session()->flash('till.just_registered', true);

        return redirect()->route('till.ticket')->with('confirm', Confirm::make(
            __('loop.customer_registered_title'),
            __('loop.customer_registered_body', ['name' => $existing->name]),
            __('loop.continue_to_sale'),
            route('till.ticket'),
            true,
        ));
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
            'pay_with_points' => ['nullable', 'boolean'],
            'points_to_spend' => ['nullable', 'integer', 'min:1'],
        ]);

        $shop = $business->shops()->whereKey($data['shop_id'])->firstOrFail();
        $phone = Countries::normalizePhone($data['phone']);
        $customer = $till->findCustomer($data['country_code'], $phone);
        $payWithPoints = $request->boolean('pay_with_points');

        if (! $customer) {
            return redirect()->route('till.index')->withErrors([
                'phone' => __('loop.customer_must_register_first'),
            ]);
        }

        if ((float) $data['amount_spent'] <= 0 && ! $payWithPoints) {
            return back()->withErrors(['amount_spent' => __('loop.amount_required')])->withInput();
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

        $body = __('loop.sale_done_body', [
            'name' => $visit->customer->name,
            'earned' => $visit->points_earned,
        ]);
        if ($visit->points_redeemed > 0) {
            $body .= ' '.__('loop.sale_done_redeemed', ['redeemed' => $visit->points_redeemed]);
            if ($visit->discount_amount > 0) {
                $body .= ' ('.$business->currency.' '.number_format((float) $visit->discount_amount, 0).')';
            }
        }
        if ($visit->notes) {
            $body .= ' — '.$visit->notes;
        }

        $request->session()->forget('till.ticket');

        return redirect()->route('till.index')->with('confirm', Confirm::make(
            __('loop.sale_done_title'),
            $body,
            __('loop.next_sale'),
            route('till.index'),
        ));
    }
}
