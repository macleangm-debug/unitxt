<?php

namespace App\Http\Controllers;

use App\Models\MessageBroadcast;
use App\Models\PaymentIntent;
use App\Models\Plan;
use App\Models\PlatformSenderId;
use App\Models\SenderId;
use App\Services\MessagingService;
use App\Services\Payments\PaymentService;
use App\Support\BillingSettings;
use App\Support\Confirm;
use App\Support\Countries;
use App\Support\FeatureFlags;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function show(Request $request, MessagingService $messaging): View|RedirectResponse
    {
        $user = $request->user();
        $business = $user?->ownedBusiness;
        abort_unless($business && $user->isOwner(), 403);

        $purpose = (string) $request->query('purpose', 'plan');
        if (! in_array($purpose, ['plan', 'sender', 'sms_credits'], true)) {
            return redirect()->route('billing.show');
        }

        $country = strtoupper((string) ($business->country ?: 'TZ'));
        $currency = $business->currency ?: Countries::currency($country);
        $checkout = [
            'purpose' => $purpose,
            'title' => __('loop.pay_now'),
            'body' => '',
            'amount' => 0,
            'currency' => $currency,
            'back' => route('billing.show'),
            'fields' => [],
        ];

        if ($purpose === 'plan') {
            $planKey = (string) $request->query('plan_key', 'growth');
            abort_unless(in_array($planKey, ['starter', 'growth', 'scale'], true), 404);
            $plan = Plan::locate($planKey, $country);
            abort_unless($plan && $plan->is_public, 404);
            $months = \App\Support\BillingSettings::normalizeMonths((int) $request->query('months', 1));
            $monthly = $business->effectiveMonthlyPrice();
            if ($monthly <= 0) {
                $monthly = (int) $plan->price_monthly;
            }
            $quote = BillingSettings::quote($monthly, $months);
            $checkout['title'] = $plan->name;
            $checkout['body'] = __('loop.payment_plan_body', [
                'plan' => $plan->name,
                'months' => $months,
            ]);
            if ($quote['save'] > 0) {
                $checkout['body'] .= ' '.__('loop.interval_save_money', [
                    'currency' => $currency,
                    'amount' => number_format($quote['save']),
                ]);
            }
            $checkout['amount'] = $quote['amount'];
            $checkout['save'] = $quote['save'];
            $checkout['months'] = $months;
            $fromPlans = $request->query('from') === 'plans';
            $checkout['back'] = $fromPlans
                ? route('billing.plans', ['months' => $months])
                : route('billing.show', ['months' => $months]);
            $checkout['fields'] = [
                'plan_key' => $plan->key,
                'months' => $months,
            ];
        }

        if ($purpose === 'sender') {
            abort_unless(FeatureFlags::enabled('sms_messaging'), 403);
            $starterId = (int) $request->query('starter', 0);
            $code = strtoupper((string) $request->query('code', ''));
            $fee = $messaging->senderYearlyFee();
            $label = $code;
            if ($starterId > 0) {
                $starter = PlatformSenderId::query()->findOrFail($starterId);
                $code = $starter->code;
                $fee = (int) ($starter->yearly_fee ?: $fee);
                $label = $starter->code;
            }
            abort_unless($code !== '' && preg_match('/^[A-Z0-9]{3,11}$/', $code), 422);
            $checkout['title'] = $label;
            $checkout['body'] = __('loop.payment_sender_body', ['code' => $label]);
            $checkout['amount'] = max(1, $fee);
            $checkout['back'] = route('members.messages.index');
            $checkout['fields'] = [
                'code' => $code,
                'starter' => $starterId > 0 ? $starterId : '',
            ];
        }

        if ($purpose === 'sms_credits') {
            abort_unless(FeatureFlags::enabled('sms_messaging'), 403);
            $credits = max(1, (int) $request->query('credits', 10));
            $broadcastId = (int) $request->query('broadcast', 0);
            $checkout['title'] = __('loop.sms_buy_credits');
            $checkout['body'] = __('loop.payment_credits_body', ['count' => $credits]);
            $checkout['amount'] = $messaging->costForMessages($credits);
            $checkout['back'] = route('members.messages.index');
            $checkout['fields'] = [
                'credits' => $credits,
                'broadcast' => $broadcastId > 0 ? $broadcastId : '',
            ];
        }

        return view('payments.show', [
            'business' => $business,
            'checkout' => $checkout,
            'country' => $country,
            'dial' => Countries::dial($country),
            'phone' => old('phone', $user->phone),
        ]);
    }

    public function checkout(Request $request, PaymentService $payments, MessagingService $messaging): RedirectResponse
    {
        $user = $request->user();
        $business = $user?->ownedBusiness;
        abort_unless($business && $user->isOwner(), 403);

        $data = $request->validate([
            'purpose' => ['required', 'in:plan,sender,sms_credits'],
            'phone' => ['required', 'string', 'max:20'],
            'plan_key' => ['nullable', 'in:starter,growth,scale'],
            'months' => ['nullable', 'integer', 'min:1', 'max:12'],
            'code' => ['nullable', 'string', 'max:11'],
            'starter' => ['nullable', 'integer'],
            'credits' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'broadcast' => ['nullable', 'integer'],
        ]);

        $country = strtoupper((string) ($business->country ?: 'TZ'));

        if ($data['purpose'] === 'plan') {
            $request->merge([
                'plan_key' => $data['plan_key'] ?? 'growth',
                'country' => $country,
                'months' => $data['months'] ?? 1,
            ]);

            return app(BillingController::class)->choose($request, app(\App\Services\PlanLimitService::class), $payments);
        }

        abort_unless(FeatureFlags::enabled('sms_messaging'), 403);
        abort_unless($messaging->countrySupported($business->country), 403);

        if ($data['purpose'] === 'sender') {
            $starterId = (int) ($data['starter'] ?? 0);
            if ($starterId > 0) {
                $starter = PlatformSenderId::query()->findOrFail($starterId);
                $sender = SenderId::query()->create([
                    'business_id' => $business->id,
                    'platform_sender_id_id' => $starter->id,
                    'code' => $starter->code,
                    'kind' => 'starter',
                    'status' => SenderId::STATUS_PENDING_PAYMENT,
                    'yearly_fee' => $starter->yearly_fee ?: $messaging->senderYearlyFee(),
                ]);
            } else {
                $code = strtoupper((string) ($data['code'] ?? ''));
                abort_unless(preg_match('/^[A-Z0-9]{3,11}$/', $code), 422);
                $sender = SenderId::query()->create([
                    'business_id' => $business->id,
                    'code' => $code,
                    'kind' => 'custom',
                    'status' => SenderId::STATUS_PENDING_PAYMENT,
                    'yearly_fee' => $messaging->senderYearlyFee(),
                ]);
            }
            $intent = $payments->startSenderIdPayment($business, $user, $sender, $data['phone'], $country);

            return redirect()->route('payments.wait', $intent);
        }

        $credits = max(1, (int) ($data['credits'] ?? 1));
        $broadcastId = (int) ($data['broadcast'] ?? 0) ?: null;
        if ($broadcastId) {
            $broadcast = MessageBroadcast::query()
                ->where('business_id', $business->id)
                ->whereKey($broadcastId)
                ->firstOrFail();
            $broadcastId = $broadcast->id;
        }
        $intent = $payments->startSmsCreditPayment($business, $user, $credits, $data['phone'], $country, $broadcastId);

        return redirect()->route('payments.wait', $intent);
    }

    public function wait(PaymentIntent $payment): View
    {
        $this->authorizePayment($payment);

        return view('payments.wait', [
            'payment' => $payment,
        ]);
    }

    public function status(PaymentIntent $payment, PaymentService $payments): JsonResponse
    {
        $this->authorizePayment($payment);
        $payment = $payments->refreshStatus($payment);

        return response()->json([
            'status' => $payment->status,
            'paid' => $payment->isPaid(),
            'terminal' => $payment->isTerminal(),
            'redirect' => $payment->isPaid()
                ? $payments->paidRedirectUrl($payment)
                : null,
        ]);
    }

    public function stubConfirm(PaymentIntent $payment, PaymentService $payments): RedirectResponse
    {
        $this->authorizePayment($payment);
        $payments->stubConfirm($payment);
        $payment = $payment->fresh();
        $url = $payments->paidRedirectUrl($payment);

        if ($payment->purpose === 'plan_upgrade') {
            return redirect()->to($url)->with('confirm', Confirm::make(
                __('loop.plan_activated_title', ['plan' => $payment->meta['plan_name'] ?? $payment->plan_key]),
                __('loop.plan_activated', ['plan' => $payment->meta['plan_name'] ?? $payment->plan_key]),
                __('loop.done'),
                $url,
                true,
            ));
        }

        if (in_array($payment->purpose, ['sender_id', 'sms_broadcast', 'sms_credits'], true)) {
            return redirect()->to($url)->with('confirm', Confirm::make(
                __('loop.payment_received_title'),
                match ($payment->purpose) {
                    'sender_id' => __('loop.sender_id_paid_body'),
                    'sms_credits' => __('loop.sms_credits_paid_body'),
                    default => __('loop.sms_paid_body'),
                },
                __('loop.done'),
                $url,
                true,
            ));
        }

        return redirect()->to($url)->with('confirm', Confirm::make(
            __('loop.payment_received_title'),
            __('loop.payment_stub_confirmed'),
            __('loop.done'),
            $url,
            false,
        ));
    }

    public function payinWebhook(Request $request, PaymentService $payments, \App\Services\Payments\PayinClient $payin): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Payin-Signature');
        $timestamp = $request->header('X-Payin-Timestamp');

        if (! $payin->verifyWebhookSignature($rawBody, $signature, $timestamp)) {
            return response()->json(['ok' => false, 'error' => 'invalid_signature'], 401);
        }

        $ref = (string) ($request->input('request_ref') ?? $request->input('data.request_ref') ?? '');
        $status = strtolower((string) ($request->input('status') ?? $request->input('data.status') ?? ''));

        if ($ref === '') {
            return response()->json(['ok' => false], 422);
        }

        $intent = PaymentIntent::query()->where('provider_ref', $ref)->first();
        if (! $intent) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        if (in_array($status, ['paid', 'success', 'successful', 'completed'], true)) {
            $payments->markPaid($intent);
        } elseif (in_array($status, ['failed', 'cancelled', 'canceled', 'expired'], true)) {
            $intent->update(['status' => PaymentIntent::STATUS_FAILED]);
        }

        return response()->json(['ok' => true]);
    }

    private function authorizePayment(PaymentIntent $payment): void
    {
        $user = request()->user();
        abort_unless($user, 403);

        if ($user->isAdmin()) {
            return;
        }

        abort_unless((int) $payment->user_id === (int) $user->id, 403);
    }
}
