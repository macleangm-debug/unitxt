<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->business) {
            return redirect()->route('dashboard');
        }

        return view('business.setup');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->business) {
            return redirect()->route('dashboard');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Business::create([
            'owner_id' => $request->user()->id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'category' => $data['category'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('dashboard')->with('status', 'Your business is ready on Loop.');
    }

    public function edit(Request $request): View
    {
        return view('business.edit', [
            'business' => $request->user()->business()->firstOrFail(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $business = $request->user()->business()->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $business->update([
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', $business->is_active),
        ]);

        return back()->with('status', 'Business profile updated.');
    }
}
