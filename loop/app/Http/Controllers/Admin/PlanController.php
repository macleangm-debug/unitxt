<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::query()->orderBy('sort_order')->get(),
        ]);
    }

    /**
     * Legacy write endpoint — plan catalog is edited only in the Settings Hub.
     */
    public function update(Request $request, Plan $plan): RedirectResponse
    {
        return redirect()->route('admin.settings', ['tab' => 'packages']);
    }
}
