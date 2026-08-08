<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Services\VisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function create(Request $request): View
    {
        return view('visits.check-in', [
            'code' => old('code', $request->query('code')),
        ]);
    }

    public function store(Request $request, VisitService $visits): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
        ]);

        $shop = Shop::query()
            ->with('business')
            ->where('code', Strtoupper(trim($data['code'])))
            ->first();

        if (! $shop) {
            return back()->withInput()->withErrors([
                'code' => 'We could not find a shop with that code.',
            ]);
        }

        $visit = $visits->checkIn($request->user(), $shop);

        return redirect()
            ->route('dashboard')
            ->with('status', "Checked in at {$shop->name}. You earned {$visit->points_earned} points!")
            ->with('points_earned_flash', (int) $visit->points_earned);
    }
}
