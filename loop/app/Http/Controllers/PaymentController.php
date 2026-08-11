<?php

namespace App\Http\Controllers;

use App\Models\PaymentIntent;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
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
                ? ($payment->purpose === 'test'
                    ? route('admin.integrations.index', ['tab' => 'console'])
                    : route('billing.show'))
                : null,
        ]);
    }

    public function stubConfirm(PaymentIntent $payment, PaymentService $payments): RedirectResponse
    {
        $this->authorizePayment($payment);
        $payments->stubConfirm($payment);

        if ($payment->fresh()->purpose === 'test') {
            return redirect()->route('admin.integrations.index', ['tab' => 'console'])
                ->with('status', __('loop.payment_stub_confirmed'));
        }

        return redirect()->route('billing.show')->with('status', __('loop.plan_activated', [
            'plan' => $payment->meta['plan_name'] ?? $payment->plan_key,
        ]));
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
