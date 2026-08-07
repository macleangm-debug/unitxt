<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        $visits = $business->visits()
            ->with(['customer', 'shop', 'recorder', 'campaign', 'reward'])
            ->latest()
            ->paginate(25);

        return view('transactions.index', [
            'business' => $business,
            'visits' => $visits,
            'isOwner' => $isOwner,
            'showAmounts' => $isOwner || \App\Support\SalesVisibility::frontDeskCanSee(),
        ]);
    }
}
