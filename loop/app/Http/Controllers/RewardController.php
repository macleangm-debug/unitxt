<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Reward;
use App\Services\MembershipService;
use App\Services\PointsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RewardController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->business()->firstOrFail();

        return view('rewards.index', [
            'business' => $business,
            'rewards' => $business->rewards()->latest()->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('rewards.create', [
            'business' => $request->user()->business()->firstOrFail(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->business()->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'points_cost' => ['required', 'integer', 'min:1'],
            'stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $business->rewards()->create([
            ...$data,
            'is_active' => true,
        ]);

        return redirect()->route('rewards.index')->with('status', 'Reward created.');
    }

    public function catalog(Request $request, Business $business): View
    {
        return view('rewards.catalog', [
            'business' => $business,
            'rewards' => $business->rewards()->where('is_active', true)->orderBy('points_cost')->get(),
            'membership' => $business->memberships()->where('customer_id', $request->user()->id)->first(),
        ]);
    }

    public function redeem(
        Request $request,
        Reward $reward,
        MembershipService $memberships,
        PointsService $points,
    ): RedirectResponse {
        abort_unless($request->user()->isCustomer(), 403);
        abort_unless($reward->isAvailable(), 422, 'This reward is not available.');

        $membership = $memberships->join($reward->business, $request->user());

        DB::transaction(function () use ($reward, $membership, $points, $request) {
            $points->redeem($membership, $reward->points_cost, "Redeemed: {$reward->name}");

            if ($reward->stock !== null) {
                $reward->decrement('stock');
            }

            $reward->redemptions()->create([
                'membership_id' => $membership->id,
                'customer_id' => $request->user()->id,
                'points_spent' => $reward->points_cost,
                'status' => 'pending',
            ]);
        });

        return back()->with('status', "Redeemed {$reward->name}. Show this at the shop to claim it.");
    }
}
