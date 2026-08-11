<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentIntent;
use App\Models\PlatformSetting;
use App\Services\Payments\PayinClient;
use App\Services\Payments\PaymentService;
use App\Support\Confirm;
use App\Support\Countries;
use App\Support\IntegrationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function index(PayinClient $payin): View|RedirectResponse
    {
        $settings = IntegrationSettings::settings();
        $tab = request('tab', 'overview');
        if ($tab === 'automation') {
            // Automation toggles moved to Settings Hub → Notifications
            return redirect()->route('admin.settings', ['tab' => 'notifications']);
        }

        return view('admin.integrations.index', [
            'settings' => $settings,
            'payinHealth' => $payin->health(),
            'payinBalance' => $payin->balance(),
            'recentPayments' => PaymentIntent::query()->latest()->limit(15)->get(),
            'tab' => $tab,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $normalized = IntegrationSettings::normalizeInput($request->all());
        PlatformSetting::putValue(IntegrationSettings::KEY, $normalized);

        return redirect()->route('admin.integrations.index', ['tab' => $request->input('return_tab', 'payments')])
            ->with('confirm', Confirm::make(
                __('loop.integrations_saved_title'),
                __('loop.integrations_saved'),
                __('loop.done'),
                route('admin.integrations.index', ['tab' => $request->input('return_tab', 'payments')]),
                false,
            ));
    }

    public function testPay(Request $request, PaymentService $payments): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:100', 'max:10000000'],
            'phone' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
        ]);

        $country = strtoupper($data['country']);
        abort_unless(isset(Countries::OPTIONS[$country]), 422);

        $intent = $payments->startTestPayment(
            $request->user(),
            (int) $data['amount'],
            $data['phone'],
            $country,
            Countries::currency($country)
        );

        return redirect()->route('payments.wait', $intent);
    }

    public function switchPrimary(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'primary' => ['required', 'in:payin'],
            'secondary' => ['nullable', 'string', 'max:40'],
        ]);

        $settings = IntegrationSettings::settings();
        $settings['payments']['primary'] = $data['primary'];
        $settings['payments']['secondary'] = $data['secondary'] ?: null;
        PlatformSetting::putValue(IntegrationSettings::KEY, $settings);

        return back()->with('status', __('loop.psp_switched'));
    }
}
