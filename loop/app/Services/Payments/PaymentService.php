<?php

namespace App\Services\Payments;

use App\Models\Business;
use App\Models\PaymentIntent;
use App\Models\Plan;
use App\Models\User;
use App\Support\Countries;
use App\Support\IntegrationSettings;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(private PayinClient $payin) {}

    public function startPlanPayment(Business $business, User $user, Plan $plan, string $localPhone, string $country): PaymentIntent
    {
        $country = strtoupper($country);
        $dial = Countries::dial($country);
        $digits = Countries::normalizePhone($localPhone);
        $fullPhone = ltrim($dial, '+').$digits;
        $currency = $plan->currency ?: Countries::currency($country);
        $provider = IntegrationSettings::primaryProvider();

        $intent = PaymentIntent::query()->create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'user_id' => $user->id,
            'purpose' => 'plan_upgrade',
            'plan_key' => $plan->key,
            'amount' => (int) $plan->price_monthly,
            'currency' => $currency,
            'phone' => $fullPhone,
            'country' => $country,
            'provider' => $provider,
            'status' => PaymentIntent::STATUS_PENDING,
            'description' => 'Loop '.$plan->name.' subscription',
            'meta' => ['plan_name' => $plan->name],
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
            $intent->business?->update([
                'plan_key' => $intent->plan_key,
                'billing_status' => 'active',
                'trial_ends_at' => null,
            ]);
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
}
