<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\MessageBroadcast;
use App\Models\PaymentIntent;
use App\Models\PlatformSenderId;
use App\Models\PlatformSetting;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\MessagingService;
use App\Services\Payments\PayinClient;
use App\Services\Payments\PaymentService;
use App\Support\Confirm;
use App\Support\Countries;
use App\Support\IntegrationSettings;
use App\Support\Sectors;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function index(PayinClient $payin, MessagingService $messaging): View|RedirectResponse
    {
        $settings = IntegrationSettings::settings();
        $tab = request('tab', 'overview');
        if ($tab === 'automation') {
            return redirect()->route('admin.settings', ['tab' => 'notifications']);
        }

        $purpose = request('purpose');
        $payments = PaymentIntent::query()
            ->when($purpose, fn ($q) => $q->where('purpose', $purpose))
            ->latest()
            ->limit(20)
            ->get();

        $membersByCountry = User::query()
            ->select('country', DB::raw('COUNT(*) as members'))
            ->where('role', User::ROLE_CUSTOMER)
            ->groupBy('country')
            ->orderByDesc('members')
            ->get();

        $businessesByCountry = Business::query()
            ->select('country', DB::raw('COUNT(*) as businesses'))
            ->groupBy('country')
            ->orderByDesc('businesses')
            ->get();

        return view('admin.integrations.index', [
            'settings' => $settings,
            'payinHealth' => $payin->health(),
            'payinBalance' => $payin->balance(),
            'smsHealth' => $messaging->health(),
            'recentPayments' => $payments,
            'subscriptionPayments' => PaymentIntent::query()->where('purpose', 'plan_upgrade')->latest()->limit(8)->get(),
            'messagingPayments' => PaymentIntent::query()->whereIn('purpose', ['sms_broadcast', 'sender_id', 'test_sms'])->latest()->limit(8)->get(),
            'tab' => $tab,
            'platformSenderIds' => PlatformSenderId::query()->orderBy('sort_order')->get(),
            'smsTemplates' => SmsTemplate::query()->orderBy('name')->get(),
            'sectors' => Sectors::all(),
            'membersByCountry' => $membersByCountry,
            'businessesByCountry' => $businessesByCountry,
            'pendingSenderIds' => \App\Models\SenderId::query()->with('business')->whereIn('status', ['pending', 'pending_payment'])->latest()->limit(15)->get(),
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

    public function testSms(Request $request, MessagingService $messaging): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
            'body' => ['required', 'string', 'max:480'],
            'sender' => ['nullable', 'string', 'max:11'],
        ]);

        $country = strtoupper($data['country']);
        abort_unless(isset(Countries::OPTIONS[$country]), 422);

        $result = $messaging->testTo(
            $data['sender'] ?: (string) (IntegrationSettings::settings()['messaging']['sender_id'] ?? 'LOOP'),
            $data['phone'],
            $data['body'],
            $country
        );

        return back()->with('confirm', Confirm::make(
            $result['ok'] ? __('loop.sms_test_ok_title') : __('loop.sms_test_fail_title'),
            $result['message'] ?? '',
            __('loop.done'),
            route('admin.integrations.index', ['tab' => 'messaging']),
            (bool) ($result['ok'] ?? false),
        ));
    }

    public function storeSenderId(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:11', 'regex:/^[A-Za-z0-9]+$/', 'unique:platform_sender_ids,code'],
            'yearly_fee' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:inactive,active'],
        ]);

        PlatformSenderId::query()->create([
            'code' => strtoupper($data['code']),
            'yearly_fee' => (int) ($data['yearly_fee'] ?? 15000),
            'status' => $data['status'] ?? 'inactive',
            'sort_order' => (int) PlatformSenderId::query()->max('sort_order') + 1,
        ]);

        return back()->with('confirm', Confirm::make(
            __('loop.starter_sender_saved_title'),
            __('loop.starter_sender_saved'),
            __('loop.done'),
            route('admin.integrations.index', ['tab' => 'messaging']),
            false,
        ));
    }

    public function updateSenderId(Request $request, PlatformSenderId $platformSenderId): RedirectResponse
    {
        $data = $request->validate([
            'yearly_fee' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:inactive,active'],
        ]);

        $platformSenderId->update([
            'yearly_fee' => (int) ($data['yearly_fee'] ?? $platformSenderId->yearly_fee),
            'status' => $data['status'],
        ]);

        return back()->with('confirm', Confirm::make(
            __('loop.starter_sender_saved_title'),
            __('loop.starter_sender_saved'),
            __('loop.done'),
            route('admin.integrations.index', ['tab' => 'messaging']),
            false,
        ));
    }

    public function activateBusinessSender(Request $request, \App\Models\SenderId $senderId): RedirectResponse
    {
        $senderId->update(['status' => \App\Models\SenderId::STATUS_ACTIVE]);

        return back()->with('confirm', Confirm::make(
            __('loop.sender_activated_title'),
            __('loop.sender_activated_body', ['code' => $senderId->code]),
            __('loop.done'),
            route('admin.integrations.index', ['tab' => 'messaging']),
            false,
        ));
    }

    public function sendBusinessSms(Request $request, MessagingService $messaging): RedirectResponse
    {
        $data = $request->validate([
            'template_key' => ['required', 'string'],
            'sector' => ['nullable', 'string', 'max:40'],
        ]);

        $template = SmsTemplate::query()->where('key', $data['template_key'])->where('is_active', true)->firstOrFail();
        $query = Business::query()->where('is_active', true)->with('owner');
        if (filled($data['sector'] ?? null)) {
            $query->where('sector', $data['sector']);
        }
        $businesses = $query->get();
        $phones = [];
        foreach ($businesses as $business) {
            $owner = $business->owner;
            if (! $owner) {
                continue;
            }
            $phones[] = ltrim(Countries::dial($business->country ?: 'TZ'), '+').Countries::normalizePhone($owner->phone);
        }

        $sender = (string) (IntegrationSettings::settings()['messaging']['sender_id'] ?? 'LOOP');
        $body = $template->body();
        $result = $messaging->testTo($sender, '700000000', $body, 'TZ');
        if ($phones !== []) {
            $result = app(\App\Services\Messaging\SmsClient::class)->send($sender, $phones, $body);
        }

        MessageBroadcast::query()->create([
            'business_id' => null,
            'user_id' => $request->user()->id,
            'sender_code' => $sender,
            'body' => $body,
            'audience' => 'businesses',
            'audience_meta' => ['sector' => $data['sector'] ?? 'all'],
            'recipient_count' => count($phones),
            'cost' => 0,
            'currency' => 'TZS',
            'status' => ($result['ok'] ?? false) ? MessageBroadcast::STATUS_SENT : MessageBroadcast::STATUS_FAILED,
            'sent_at' => now(),
            'template_key' => $template->key,
            'sector' => $data['sector'] ?? null,
            'purpose' => 'admin_sms',
        ]);

        return back()->with('confirm', Confirm::make(
            __('loop.admin_sms_sent_title'),
            __('loop.admin_sms_sent_body', ['count' => count($phones)]),
            __('loop.done'),
            route('admin.integrations.index', ['tab' => 'messaging']),
            (bool) ($result['ok'] ?? false),
        ));
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
