<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->ownedBusiness ?? $request->user()->workplace();
        abort_unless($business && $request->user()->isStaff(), 403);

        $isOwner = $request->user()->isOwner();
        if (! $isOwner && ! \App\Support\SalesVisibility::frontDeskCanSee()) {
            abort(403, __('loop.sales_hidden_for_front_desk'));
        }

        $q = trim((string) $request->query('q', ''));
        $shopId = $request->query('shop');
        $channel = $request->query('channel');
        $period = $request->query('period', 'all');

        $base = $business->visits()->with(['customer', 'shop', 'recorder', 'campaign', 'reward']);

        $filtered = (clone $base)
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('customer', function ($c) use ($q) {
                    $c->where('first_name', 'like', '%'.$q.'%')
                        ->orWhere('last_name', 'like', '%'.$q.'%')
                        ->orWhere('phone', 'like', '%'.$q.'%');
                });
            })
            ->when($shopId, fn ($query) => $query->where('shop_id', $shopId))
            ->when(in_array($channel, ['in_store', 'phone_order'], true), fn ($query) => $query->where('channel', $channel))
            ->when($period === 'today', fn ($query) => $query->whereDate('created_at', today()))
            ->when($period === 'week', fn ($query) => $query->where('created_at', '>=', now()->startOfWeek()))
            ->when($period === 'month', fn ($query) => $query->where('created_at', '>=', now()->startOfMonth()));

        $visits = $filtered->latest()->paginate(25)->withQueryString();

        $todaySpend = (clone $base)->whereDate('created_at', today())->sum('amount_spent');
        $todayCount = (clone $base)->whereDate('created_at', today())->count();
        $weekSpend = (clone $base)->where('created_at', '>=', now()->startOfWeek())->sum('amount_spent');
        $monthSpend = (clone $base)->where('created_at', '>=', now()->startOfMonth())->sum('amount_spent');

        return view('transactions.index', [
            'business' => $business,
            'visits' => $visits,
            'shops' => $business->shops()->where('is_active', true)->orderBy('name')->get(),
            'isOwner' => $isOwner,
            'showAmounts' => $isOwner || \App\Support\SalesVisibility::frontDeskCanSee(),
            'filters' => [
                'q' => $q,
                'shop' => $shopId,
                'channel' => $channel,
                'period' => $period,
            ],
            'summary' => [
                'today_count' => $todayCount,
                'today_spend' => (float) $todaySpend,
                'week_spend' => (float) $weekSpend,
                'month_spend' => (float) $monthSpend,
            ],
        ]);
    }
}
