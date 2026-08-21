<?php

namespace App\Services\Payments;

use App\Models\Business;
use App\Models\MessageBroadcast;
use App\Models\PaymentIntent;
use App\Models\Plan;
use App\Models\SenderId;
use App\Models\User;
use App\Services\LoopAccess;
use App\Services\MessagingService;
use App\Support\BillingSettings;
use App\Support\Countries;
use App\Support\IntegrationSettings;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(private PayinClient $payin) {}

    public function startPlanPayment(Business $business, User $user, Plan $plan, string $localPhone, string $country, int $months = 1): PaymentIntent
    {
        $country = strtoupper($country);
        $dial = Countries::dial($country);
        $digits = Countries::normalizePhone($localPhone);
        $fullPhone = ltrim($dial, '+').$digits;
        $currency = $plan->currency ?: Countries::currency($country);
        $provider = IntegrationSettings::primaryProvider();
        $months = in_array($months, [1, 3, 6, 12], true) ? $months : 1;
        $monthly = $business->effectiveMonthlyPrice();
        if ($monthly <= 0) {
            $monthly = (int) $plan->price_monthly;
        }
        $amount = BillingSettings::amountForMonths($monthly, $months);
        $discount = BillingSettings::discountForMonths($months);

        $intent = PaymentIntent::query()->create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'user_id' => $user->id,
            'purpose' => 'plan_upgrade',
            'plan_key' => $plan->key,
            'amount' => $amount,
            'currency' => $currency,
            'phone' => $fullPhone,
            'country' => $country,
            'provider' => $provider,
            'status' => PaymentIntent::STATUS_PENDING,
            'description' => 'Loop '.$plan->name.' subscription · '.$months.' month(s)',
            'meta' => [
                'plan_name' => $plan->name,
                'months' => $months,
                'discount_percent' => $discount,
            ],
        ]);

        return $this->dispatchCollection($intent);
    }

    public function startSenderIdPayment(Business $business, User $user, SenderId $senderId, string $localPhone, string $country): PaymentIntent
    {
        $country = strtoupper($country);
        $dial = Countries::dial($country);
        $digits = Countries::normalizePhone($localPhone);
        $fullPhone = ltrim($dial, '+').$digits;
        $amount = (int) ($senderId->yearly_fee ?: app(MessagingService::class)->senderYearlyFee());

        $intent = PaymentIntent::query()->create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'user_id' => $user->id,
            'purpose' => 'sender_id',
            'plan_key' => null,
            'amount' => max(1, $amount),
            'currency' => $business->currency ?: Countries::currency($country),
            'phone' => $fullPhone,
            'country' => $country,
            'provider' => IntegrationSettings::primaryProvider(),
            'status' => PaymentIntent::STATUS_PENDING,
            'description' => 'Loop Sender ID '.$senderId->code.' · 12 months',
            'meta' => ['sender_id_id' => $senderId->id, 'code' => $senderId->code],
        ]);

        return $this->dispatchCollection($intent);
    }

    public function startSmsBroadcastPayment(Business $business, User $user, MessageBroadcast $broadcast, string $localPhone, string $country): PaymentIntent
    {
        $country = strtoupper($country);
        $dial = Countries::dial($country);
        $digits = Countries::normalizePhone($localPhone);
        $fullPhone = ltrim($dial, '+').$digits;

        $intent = PaymentIntent::query()->create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'user_id' => $user->id,
            'purpose' => 'sms_broadcast',
            'plan_key' => null,
            'amount' => max(1, (int) $broadcast->cost),
            'currency' => $broadcast->currency ?: $business->currency,
            'phone' => $fullPhone,
            'country' => $country,
            'provider' => IntegrationSettings::primaryProvider(),
            'status' => PaymentIntent::STATUS_PENDING,
            'description' => 'Loop member SMS · '.$broadcast->recipient_count.' messages',
            'meta' => ['broadcast_id' => $broadcast->id],
        ]);

        $broadcast->update([
            'payment_intent_id' => $intent->id,
            'status' => MessageBroadcast::STATUS_PENDING_PAYMENT,
        ]);

        return $this->dispatchCollection($intent);
    }

    public function startTestPayment(User $user, int $amount, string $localPhone, string $country, string $currency = 'TZS'): PaymentIntent
    {
        $country = strtoupper($country);
        $dial = Countries::dial($country);
        $digits = Countries::normalizePhone($localPhone);
        $fullPhone = ltrim($dial, '+').$digits;

        $intent = PaymentIntent::query()->create([
            'uuid' => (string) Str::uuid(),
            'business_id' => null,
            'user_id' => $user->id,
            'purpose' => 'test',
            'plan_key' => null,
            'amount' => max(100, $amount),
            'currency' => $currency,
            'phone' => $fullPhone,
            'country' => $country,
            'provider' => IntegrationSettings::primaryProvider(),
            'status' => PaymentIntent::STATUS_PENDING,
            'description' => 'Loop payment console test',
            'meta' => ['test' => true],
        ]);

        return $this->dispatchCollection($intent);
    }

    public function dispatchCollection(PaymentIntent $intent): PaymentIntent
    {
        $result = $this->payin->collect(
            $intent->phone,
            $intent->amount,
            $intent->currency,
            'LOOP-'.$intent->uuid,
            (string) $intent->description,
            route('payments.webhook.payin')
        );

        $intent->update([
            'provider_ref' => $result['request_ref'] ?? $intent->provider_ref,
            'status' => ($result['ok'] ?? false) ? PaymentIntent::STATUS_PROCESSING : PaymentIntent::STATUS_FAILED,
            'meta' => array_merge($intent->meta ?? [], ['collect' => $result]),
        ]);

        return $intent->fresh();
    }

    public function refreshStatus(PaymentIntent $intent): PaymentIntent
    {
        if ($intent->isTerminal() || ! $intent->provider_ref) {
            return $intent;
        }

        $result = $this->payin->status($intent->provider_ref);
        $status = strtolower((string) ($result['status'] ?? 'processing'));

        if (in_array($status, ['paid', 'success', 'successful', 'completed'], true)) {
            $this->markPaid($intent);

            return $intent->fresh();
        }

        if (in_array($status, ['failed', 'cancelled', 'canceled', 'expired'], true)) {
            $intent->update([
                'status' => PaymentIntent::STATUS_FAILED,
                'meta' => array_merge($intent->meta ?? [], ['status_check' => $result]),
            ]);
        }

        return $intent->fresh();
    }

    public function markPaid(PaymentIntent $intent): void
    {
        if ($intent->isPaid()) {
            return;
        }

        $intent->update([
            'status' => PaymentIntent::STATUS_PAID,
            'paid_at' => now(),
        ]);

        if ($intent->purpose === 'plan_upgrade' && $intent->business_id && $intent->plan_key) {
            $months = (int) (($intent->meta['months'] ?? 1) ?: 1);
            $business = $intent->business;
            if ($business) {
                $plan = Plan::locate($intent->plan_key, $business->country);
                $monthly = (int) ($plan?->price_monthly ?? 0);
                if (($business->referral_credit_months ?? 0) > 0) {
                    $monthly = 0;
                } elseif ($monthly > 0) {
                    $monthly = (int) round($monthly * (100 - min(100, max(0, (int) $business->referral_discount_percent))) / 100);
                }
                app(LoopAccess::class)->activate($business, $intent->plan_key, $months, $monthly);
            }
        }

        if ($intent->purpose === 'sender_id') {
            $senderId = SenderId::query()->find($intent->meta['sender_id_id'] ?? 0);
            if ($senderId) {
                $until = ($senderId->paid_until && $senderId->paid_until->isFuture())
                    ? $senderId->paid_until->copy()->addYear()
                    : now()->addYear();
                $senderId->update([
                    'paid_until' => $until,
                    'status' => SenderId::STATUS_PENDING,
                ]);
            }
        }

        if ($intent->purpose === 'sms_broadcast') {
            $broadcast = MessageBroadcast::query()->find($intent->meta['broadcast_id'] ?? $intent->meta['broadcast_id'] ?? 0);
            if (! $broadcast && $intent->business_id) {
                $broadcast = MessageBroadcast::query()
                    ->where('payment_intent_id', $intent->id)
                    ->first();
            }
            if ($broadcast && $broadcast->business) {
                $messaging = app(MessagingService::class);
                $audience = array_merge(['audience' => $broadcast->audience], $broadcast->audience_meta ?? []);
                $users = $messaging->recipients($broadcast->business, $audience);
                $phones = $messaging->phonesFor($users, $broadcast->business->country ?: 'TZ');
                $messaging->deliver($broadcast, $phones);
            }
        }
    }

    /** Stub console helper: force-confirm a processing payment (sandbox/UI only). */
    public function stubConfirm(PaymentIntent $intent): PaymentIntent
    {
        if (! str_starts_with((string) $intent->provider_ref, 'STUB-')) {
            return $intent;
        }

        $this->markPaid($intent);

        return $intent->fresh();
    }

    public function paidRedirectUrl(PaymentIntent $intent): string
    {
        return match ($intent->purpose) {
            'test' => route('admin.integrations.index', ['tab' => 'console']),
            'test_sms', 'admin_sms' => route('admin.integrations.index', ['tab' => 'messaging']),
            'sender_id', 'sms_broadcast' => route('members.messages.index'),
            default => route('billing.show'),
        };
    }
}
