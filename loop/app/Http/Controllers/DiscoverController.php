<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Campaign;
use App\Support\Sectors;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscoverController extends Controller
{
    public function __invoke(Request $request): View
    {
        $sector = $request->query('sector');

        $businesses = Business::query()
            ->where('is_active', true)
            ->when($sector, fn ($q) => $q->where('sector', $sector))
            ->with(['campaigns' => fn ($q) => $q->active()])
            ->withCount('shops')
            ->latest()
            ->get()
            ->groupBy('sector');

        return view('discover.index', [
            'grouped' => $businesses,
            'sectors' => Sectors::OPTIONS,
            'activeSector' => $sector,
        ]);
    }

    public function show(Business $business): View
    {
        abort_unless($business->is_active, 404);

        return view('discover.show', [
            'business' => $business->load(['shops' => fn ($q) => $q->where('is_active', true)]),
            'campaigns' => $business->campaigns()->active()->get(),
            'rewards' => $business->rewards()->where('is_active', true)->orderBy('points_cost')->get(),
        ]);
    }
}
