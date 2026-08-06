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

        $visits = $business->visits()
            ->with(['customer', 'shop', 'recorder', 'campaign', 'reward'])
            ->latest()
            ->paginate(25);

        return view('transactions.index', [
            'business' => $business,
            'visits' => $visits,
            'isOwner' => $request->user()->isOwner(),
        ]);
    }
}
