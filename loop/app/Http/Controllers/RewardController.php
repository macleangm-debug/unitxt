<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RewardController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        return view('rewards.index', [
            'business' => $business,
            'rewards' => $business->rewards()->latest()->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('rewards.create', [
            'business' => $request->user()->ownedBusiness()->firstOrFail(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness()->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'points_cost' => ['required', 'integer', 'min:1'],
            'reward_type' => ['required', 'in:percent_off,fixed_off,free_item,custom'],
            'reward_value' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $business->rewards()->create([
            ...$data,
            'is_active' => true,
        ]);

        return redirect()->route('rewards.index')->with('status', 'Reward created. Staff can apply it at the till.');
    }
}
