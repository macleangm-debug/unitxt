<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\Confirm;
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

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'price_monthly' => ['required', 'integer', 'min:0', 'max:100000000'],
            'currency' => ['required', 'string', 'size:3'],
            'max_shops' => ['nullable', 'integer', 'min:1', 'max:500'],
            'max_members' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'max_monthly_visits' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100'],
            'is_public' => ['sometimes', 'boolean'],
            'features_text' => ['nullable', 'string', 'max:4000'],
        ]);

        $features = collect(preg_split('/\r\n|\r|\n/', (string) ($data['features_text'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $plan->update([
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? '',
            'price_monthly' => $data['price_monthly'],
            'currency' => strtoupper($data['currency']),
            'max_shops' => $data['max_shops'] ?? null,
            'max_members' => $data['max_members'] ?? null,
            'max_monthly_visits' => $data['max_monthly_visits'] ?? null,
            'sort_order' => $data['sort_order'],
            'is_public' => $request->boolean('is_public'),
            'features' => $features,
        ]);

        return redirect()
            ->route('admin.settings', ['tab' => 'packages'])
            ->with('confirm', Confirm::make(
                __('loop.admin_plan_saved_title'),
                __('loop.admin_plan_saved', ['name' => $plan->name]),
                __('loop.done'),
                route('admin.settings', ['tab' => 'packages']),
                false,
            ));
    }
}
