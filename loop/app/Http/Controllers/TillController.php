<?php

namespace App\Http\Controllers;

use App\Services\PlanLimitService;
use App\Services\TillService;
use App\Support\Confirm;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        $loopPaused = $business ? app(\App\Services\LoopAccess::class)->isPaused($business) : false;
        $isOwner = $request->user()->isOwner();

        $scanDial = null;
        $scanPhone = null;
        $scan = (string) $request->query('scan', '');
        if ($scan !== '' && str_contains($scan, '|')) {
            [$scanDial, $scanPhone] = array_pad(explode('|', $scan, 2), 2, null);
            $scanDial = $scanDial ? '+'.ltrim(preg_replace('/\D+/', '', $scanDial), '+') : null;
            $scanPhone = $scanPhone ? preg_replace('/\D+/', '', $scanPhone) : null;
        }

        $shops = $business?->id
            ? $request->user()->tillShops($business)
            : collect();

        if ($request->boolean('change')) {
            $request->session()->forget('till.shop_id');
        }

        $activeShop = null;
        if ($shops->count() === 1) {
            $activeShop = $shops->first();
            $request->session()->put('till.shop_id', $activeShop->id);
        } elseif ($shops->count() > 1) {
            $requested = $request->query('shop_id');
            $sessionShopId = filled($requested) ? $requested : $request->session()->get('till.shop_id');
            $activeShop = $shops->firstWhere('id', (int) $sessionShopId);
            if ($activeShop) {
                $request->session()->put('till.shop_id', $activeShop->id);
            }
        }

        return view('till.index', [
            'business' => $business,
            'shops' => $shops,
            'activeShop' => $activeShop,
            'needsBranchPick' => $shops->count() > 1 && ! $activeShop,
            'countries' => Countries::formOptions($business?->country),
            'recent' => ($isOwner && $business)
                ? $business->visits()->with(['customer', 'shop', 'recorder'])->latest()->take(8)->get()
                : collect(),
            'showRecent' => $isOwner,
            'tillLocked' => $tillLocked,
            'loopPaused' => $loopPaused,
            'isOwner' => $isOwner,
            'scanDial' => $scanDial,
            'scanPhone' => $scanPhone,
            'scanQuery' => $scan !== '' ? $scan : null,
            'channel' => $request->session()->get('till.channel', 'in_store'),
        ]);
    }

    public function pickBranch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shop_id' => ['required', 'exists:shops,id'],
        ]);

        $business = $request->user()->workplace();
        abort_unless($business, 403);

        $shop = $business->shops()->whereKey($data['shop_id'])->firstOrFail();
        abort_unless($request->user()->canAccessShop($shop), 403);

        $request->session()->put('till.shop_id', $shop->id);

        $query = [];
        $scan = (string) $request->input('scan', $request->query('scan', ''));
        if ($scan !== '') {
            $query['scan'] = $scan;
        }

        return redirect()->route('till.index', $query);
    }

    public function lookup(Request $request, TillService $till): RedirectResponse
    {
        $data = $request->validate([
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'channel' => ['required', 'in:in_store,phone_order'],
        ]);

        $business = $request->user()->workplace();
        abort_unless($business, 403);

        $shopId = $data['shop_id'] ?? $request->session()->get('till.shop_id');
        if (! $shopId) {
            return redirect()->route('till.index')->withErrors([
                'shop_id' => __('loop.choose_branch'),
            ]);
        }

        $shop = $business->shops()->whereKey($shopId)->firstOrFail();
        abort_unless($request->user()->canAccessShop($shop), 403);
        $request->session()->put('till.shop_id', $shop->id);
        $request->session()->put('till.channel', $data['channel']);
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

        $shopScope = function ($query) use ($shop) {
            $query->whereDoesntHave('shops')
                ->orWhereHas('shops', fn ($shops) => $shops->where('shops.id', $shop->id));
        };

        $campaign = $business->campaigns()
            ->active()
            ->where('type', 'earn')
            ->where($shopScope)
            ->orderByDesc('points_per_step')
            ->first();

        $productPushes = $business->campaigns()
            ->active()
            ->where('type', 'product_push')
            ->where($shopScope)
            ->whereNotNull('featured_product_name')
            ->orderBy('featured_product_name')
            ->get();

        $membership = $customer
            ? $business->memberships()->where('customer_id', $customer->id)->first()
            : null;

        $rewards = $business->rewards()->where('is_active', true)->orderBy('points_cost')->get();
        $availableOffers = $membership ? $membership->availableRewards() : collect();
        $raffleWins = ($customer && $membership && \App\Support\FeatureFlags::enabled('raffles'))
            ? app(\App\Services\RaffleService::class)->openWinsForCustomer($business, (int) $customer->id)
            : collect();
        $nextOffer = $membership?->nextReward();

        $errorStep = 1;
        if (($availableOffers->isNotEmpty() || $raffleWins->isNotEmpty()) && old('amount_spent')) {
            $errorStep = 2;
        }

        $loopPaused = app(\App\Services\LoopAccess::class)->isPaused($business);

        return view('till.sale', [
            'business' => $business,
            'shop' => $shop,
            'channel' => $ticket['channel'],
            'country_code' => $ticket['country_code'],
            'phone' => $ticket['phone'],
            'customer' => $customer,
            'membership' => $membership,
            'rewards' => $rewards,
            'availableOffers' => $availableOffers,
            'raffleWins' => $raffleWins,
            'offerCards' => $availableOffers->map(fn ($reward) => [
                'id' => (string) $reward->id,
                'kind' => 'reward',
                'name' => $reward->name,
                'type' => $reward->reward_type,
                'points' => $reward->points_cost,
                'value' => (float) $reward->reward_value,
                'label' => $reward->label(),
                'needs_bill' => $reward->needsBill(),
            ])->concat($raffleWins->map(fn ($win) => [
                'id' => (string) $win->id,
                'kind' => 'raffle',
                'name' => $win->raffle->prize_name,
                'type' => $win->raffle->prize_type,
                'points' => 0,
                'value' => (float) $win->raffle->prize_value,
                'label' => $win->raffle->name,
                'needs_bill' => in_array($win->raffle->prize_type, ['percent_off', 'fixed_off'], true),
            ]))->values()->all(),
            'campaign' => $campaign,
            'productPushes' => $productPushes,
            'nextOffer' => $nextOffer,
            'initialStep' => $errorStep,
            'needsRegister' => (bool) ($ticket['needs_register'] ?? false) && ! $customer,
            'loopPaused' => $loopPaused,
        ]);
    }

    public function registered(Request $request): View|RedirectResponse
    {
        $ticket = $request->session()->get('till.ticket');
        if (! is_array($ticket) || empty($ticket['customer_id'])) {
            return redirect()->route('till.index');
        }

        return view('till.registered');
    }

    public function registerCustomer(Request $request, TillService $till): RedirectResponse
    {
        $business = $request->user()->workplace();
        abort_unless($business && $request->user()->canUseTill(), 403);
        abort_unless(! app(\App\Services\LoopAccess::class)->isPaused($business), 403);

        $ticket = $request->session()->get('till.ticket');
        abort_unless(is_array($ticket), 403);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'birth_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'birth_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'gender' => ['nullable', 'in:male,female'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $shop = $business->shops()->whereKey($ticket['shop_id'] ?? null)->first();

        try {
            $existing = $till->registerCustomer([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? '',
                'country_code' => $ticket['country_code'],
                'phone' => $ticket['phone'],
                'email' => $data['email'] ?? null,
                'birth_month' => $data['birth_month'] ?? null,
                'birth_day' => $data['birth_day'] ?? null,
                'gender' => $data['gender'] ?? null,
                'city' => $shop?->city,
            ]);
        } catch (ValidationException $e) {
            $request->session()->forget('till.ticket');

            return redirect()->route('till.index')->withErrors($e->errors());
        }

        $ticket['customer_id'] = $existing->id;
        $ticket['needs_register'] = false;
        $request->session()->put('till.ticket', $ticket);

        return redirect()->route('till.registered')->with('confirm', Confirm::make(
            __('loop.customer_registered_title'),
            __('loop.customer_registered_body', ['name' => $existing->name]),
            __('loop.continue_to_sale'),
            route('till.ticket'),
            true,
            ['must_continue' => true],
        ));
    }

    public function store(Request $request, TillService $till): RedirectResponse
    {
        $business = $request->user()->workplace();
        abort_unless($business && $request->user()->canUseTill(), 403);
        $business = $business->fresh();

        $amountRaw = str_replace([',', ' '], '', (string) $request->input('amount_spent', '0'));
        $request->merge(['amount_spent' => $amountRaw]);

        $data = $request->validate([
            'shop_id' => ['required', 'exists:shops,id'],
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
            'channel' => ['required', 'in:in_store,phone_order'],
            'amount_spent' => ['required', 'numeric', 'min:0'],
            'receipt_ref' => ['nullable', 'string', 'max:80'],
            'pay_with_points' => ['nullable', 'boolean'],
            'points_to_spend' => ['nullable', 'integer', 'min:1'],
            'includes_featured_product' => ['nullable', 'boolean'],
            'featured_campaign_ids' => ['nullable', 'array'],
            'featured_campaign_ids.*' => ['integer'],
            'reward_id' => ['nullable', 'integer', 'exists:rewards,id'],
            'raffle_winner_id' => ['nullable', 'integer', 'exists:raffle_winners,id'],
        ]);

        $shop = $business->shops()->whereKey($data['shop_id'])->firstOrFail();
        abort_unless($request->user()->canAccessShop($shop), 403);
        $phone = Countries::normalizePhone($data['phone']);
        $customer = $till->findCustomer($data['country_code'], $phone);
        $payWithPoints = $request->boolean('pay_with_points');
        $includesFeatured = $request->boolean('includes_featured_product');
        $featuredIds = collect($request->input('featured_campaign_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        if ($featuredIds !== []) {
            $includesFeatured = $featuredIds;
        }

        if (! $customer) {
            return redirect()->route('till.index')->withErrors([
                'phone' => __('loop.customer_must_register_first'),
            ]);
        }

        $reward = null;
        if (! empty($data['reward_id'])) {
            $reward = $business->rewards()->whereKey((int) $data['reward_id'])->first();
            if (! $reward) {
                return back()->withErrors(['reward_id' => __('loop.till_offer_gone')])->withInput();
            }
        }

        $raffleWinnerId = ! empty($data['raffle_winner_id']) ? (int) $data['raffle_winner_id'] : null;
        if ($reward && $raffleWinnerId) {
            return back()->withErrors(['raffle_winner_id' => __('loop.till_raffle_or_offer')])->withInput();
        }

        $amount = (float) $data['amount_spent'];
        $hasFeatured = $featuredIds !== [] || $includesFeatured === true;

        $rafflePrize = null;
        if ($raffleWinnerId) {
            $rafflePrize = \App\Models\RaffleWinner::query()->with('raffle')->find($raffleWinnerId);
        }
        $raffleIsFree = $rafflePrize && in_array($rafflePrize->raffle?->prize_type, ['free_item', 'custom'], true);

        if ($reward?->isFreeRedeem() && $amount <= 0 && ! $hasFeatured && ! $payWithPoints) {
            try {
                $redemption = $till->redeemOffer(
                    $request->user(),
                    $shop,
                    $customer,
                    $reward->id,
                );
            } catch (ValidationException $e) {
                return back()->withInput()->withErrors($e->errors());
            }
            $request->session()->forget('till.ticket');
            $balance = (int) ($redemption->membership?->fresh()?->points_balance ?? 0);

            return $this->saleMoment($request, $customer, $business, [
                'title' => __('loop.redeem_done_title'),
                'body' => __('loop.redeem_done_body', [
                    'name' => $customer->name,
                    'offer' => $redemption->reward->name,
                    'points' => $redemption->points_spent,
                ]),
                'earned' => 0,
                'from' => $balance + (int) $redemption->points_spent,
                'to' => $balance,
                'unlock' => $redemption->reward?->name,
                'unlock_when' => 'now',
                'visit' => null,
            ]);
        }

        if ($payWithPoints && ! $business->payWithPointsEnabled()) {
            return back()->withErrors(['pay_with_points' => __('loop.pay_with_points_disabled')])->withInput();
        }

        if ($reward && $payWithPoints) {
            return back()->withErrors(['pay_with_points' => __('loop.till_no_pay_points_with_offer')])->withInput();
        }
        if ($raffleWinnerId && $payWithPoints) {
            return back()->withErrors(['pay_with_points' => __('loop.till_no_pay_points_with_offer')])->withInput();
        }

        if ($amount <= 0 && ! $payWithPoints && ! ($reward?->isFreeRedeem() && $hasFeatured) && ! $raffleIsFree) {
            return back()->withErrors(['amount_spent' => __('loop.amount_required')])->withInput();
        }

        $visit = $till->recordSale(
            $request->user(),
            $shop,
            $customer,
            $amount,
            $data['receipt_ref'] ?? null,
            $data['channel'],
            $payWithPoints,
            $payWithPoints ? ($data['points_to_spend'] ?? null) : null,
            $includesFeatured,
            $reward?->id,
            $raffleWinnerId,
        );

        $visit = $visit->fresh(['customer', 'membership', 'reward']);
        $to = (int) ($visit->membership?->points_balance ?? 0);
        $from = max(0, $to - (int) $visit->points_earned + (int) $visit->points_redeemed);
        $unlock = $till->unlockAfterSale($visit);
        $gamePlay = \App\Support\GameSettings::tablesReady()
            ? \App\Models\GamePlay::query()->where('visit_id', $visit->id)->latest('id')->first()
            : null;

        $request->session()->forget('till.ticket');

        return $this->saleMoment($request, $customer, $business, [
            'title' => __('loop.sale_done_title'),
            'body' => __('loop.sale_done_body', [
                'name' => $visit->customer->name,
                'earned' => $visit->points_earned,
            ]),
            'earned' => (int) $visit->points_earned,
            'from' => $from,
            'to' => $to,
            'amount' => $business->currency.' '.number_format((float) $visit->amount_spent, 0),
            'unlock' => $unlock['reward']?->name,
            'unlock_when' => $unlock['when'],
            'visit' => $visit,
            'game_play' => $gamePlay,
        ]);
    }

    public function undo(Request $request, \App\Models\Visit $visit, TillService $till): RedirectResponse
    {
        try {
            $till->undoSale($request->user(), $visit);
        } catch (ValidationException $e) {
            return redirect()->route('till.index')->withErrors($e->errors());
        }

        return redirect()->route('till.index')->with('status', __('loop.sale_undone'));
    }

    public function redeem(Request $request, TillService $till): RedirectResponse
    {
        $business = $request->user()->workplace();
        abort_unless($business && $request->user()->canUseTill(), 403);

        $data = $request->validate([
            'shop_id' => ['required', 'exists:shops,id'],
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
            'reward_id' => ['required', 'exists:rewards,id'],
            'notes' => ['nullable', 'string', 'max:255'],
            'continue_to_sale' => ['nullable', 'boolean'],
        ]);

        $shop = $business->shops()->whereKey($data['shop_id'])->firstOrFail();
        abort_unless($request->user()->canAccessShop($shop), 403);
        $phone = Countries::normalizePhone($data['phone']);
        $customer = $till->findCustomer($data['country_code'], $phone);

        if (! $customer) {
            return redirect()->route('till.index')->withErrors([
                'phone' => __('loop.customer_must_register_first'),
            ]);
        }

        try {
            $redemption = $till->redeemOffer(
                $request->user(),
                $shop,
                $customer,
                (int) $data['reward_id'],
                $data['notes'] ?? null,
            );
        } catch (ValidationException $e) {
            $fallback = $request->session()->has('till.ticket')
                ? redirect()->route('till.ticket')
                : redirect()->route('till.index');

            return $fallback->withInput()->withErrors($e->errors());
        }

        $body = __('loop.redeem_done_body', [
            'name' => $customer->name,
            'offer' => $redemption->reward->name,
            'points' => $redemption->points_spent,
        ]);
        if ($redemption->notes) {
            $body .= ' — '.$redemption->notes;
        }

        if ($request->boolean('continue_to_sale')) {
            return redirect()->route('till.ticket', ['mode' => 'sale'])->with('confirm', Confirm::make(
                __('loop.redeem_done_title'),
                $body.' '.__('loop.redeem_then_sale_hint'),
                __('loop.continue_to_sale'),
                route('till.ticket', ['mode' => 'sale']),
                true,
            ));
        }

        $request->session()->forget('till.ticket');

        return redirect()->route('till.index')->with('confirm', Confirm::make(
            __('loop.redeem_done_title'),
            $body,
            __('loop.next_sale'),
            route('till.index'),
        ));
    }

    /**
     * @param  array{title: string, body: string, earned: int, from: int, to: int, amount?: string, unlock?: ?string, unlock_when?: ?string, visit: ?\App\Models\Visit}  $moment
     */
    private function saleMoment(Request $request, $customer, $business, array $moment): RedirectResponse
    {
        $visit = $moment['visit'] ?? null;
        $extra = [
            'loop_moment' => [
                'name' => $customer->name,
                'from' => (int) $moment['from'],
                'to' => (int) $moment['to'],
                'earned' => (int) $moment['earned'],
                'amount' => $moment['amount'] ?? null,
                'unlock' => $moment['unlock'] ?? null,
                'unlock_when' => $moment['unlock_when'] ?? null,
            ],
        ];
        if ($visit) {
            $extra['undo_url'] = route('till.undo', $visit);
        }
        if (! empty($moment['game_play'])) {
            $play = $moment['game_play'];
            $extra['game_play'] = [
                'title' => __('loop.game_unlocked_play', ['name' => $customer->first_name ?: $customer->name]),
                'game' => $play->game?->typeLabel() ?? __('loop.games_wins'),
                'url' => route('games.play', $play),
            ];
        }

        return redirect()->route('till.index')->with('confirm', Confirm::make(
            $moment['title'],
            $moment['body'],
            __('loop.next_customer'),
            route('till.index'),
            true,
            $extra,
        ));
    }
}
