<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivacyRequest;
use App\Support\Confirm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrivacyController extends Controller
{
    public function index(): View
    {
        return view('admin.privacy.index', [
            'requests' => PrivacyRequest::query()->with('user')->latest()->paginate(40),
        ]);
    }

    public function resolve(Request $request, PrivacyRequest $privacyRequest): RedirectResponse
    {
        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $privacyRequest->update([
            'status' => PrivacyRequest::STATUS_RESOLVED,
            'admin_notes' => $data['admin_notes'] ?? $privacyRequest->admin_notes,
            'resolved_at' => now(),
        ]);

        return back()->with('confirm', Confirm::make(
            __('loop.privacy_resolved_title'),
            __('loop.privacy_resolved'),
            __('loop.done'),
            route('admin.privacy.index'),
            false,
        ));
    }
}
