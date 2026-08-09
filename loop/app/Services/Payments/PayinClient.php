<?php

namespace App\Services\Payments;

use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PayIn mobile-money PSP client (https://docs.payin.co.tz/).
 * Supports sandbox + live. Without API keys, runs in local stub mode for UX testing.
 */
class PayinClient
{
    /**
     * @return array{ok: bool, request_ref: ?string, status: string, message: string, raw?: mixed}
     */
    public function collect(string $phone, int $amount, string $currency, string $reference, string $description, ?string $callbackUrl = null): array
    {
        $cfg = IntegrationSettings::provider('payin');
        $mode = $cfg['mode'] ?? 'sandbox';
        $key = trim((string) ($cfg['api_key'] ?? ''));
        $secret = trim((string) ($cfg['api_secret'] ?? ''));

        if ($key === '' || $secret === '') {
            return [
                'ok' => true,
                'request_ref' => 'STUB-'.Str::upper(Str::random(10)),
                'status' => 'processing',
                'message' => 'Stub collection started (no PayIn keys configured). Confirm PIN on device simulation.',
                'stub' => true,
            ];
        }

        $base = $mode === 'live'
            ? 'https://api.payin.co.tz/api/v1'
            : 'https://api.sandbox.payin.co.tz/api/v1';

        $payload = [
            'phone' => preg_replace('/\D+/', '', $phone),
            'amount' => $amount,
            'currency' => $currency,
            'reference' => $reference,
            'description' => $description,
        ];
        if ($callbackUrl) {
            $payload['callback_url'] = $callbackUrl;
        }

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $key,
                'X-API-Secret' => $secret,
                'X-Idempotency-Key' => (string) Str::uuid(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(20)->post($base.'/collection', $payload);

            $json = $response->json() ?? [];
            if ($response->successful()) {
                return [
                    'ok' => true,
                    'request_ref' => $json['request_ref'] ?? null,
                    'status' => (string) ($json['status'] ?? 'processing'),
                    'message' => (string) ($json['message'] ?? 'Collection sent'),
                    'raw' => $json,
                ];
            }

            return [
                'ok' => false,
                'request_ref' => $json['request_ref'] ?? null,
                'status' => 'failed',
                'message' => (string) ($json['message'] ?? 'PayIn collection failed'),
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            Log::warning('PayIn collect failed', ['error' => $e->getMessage()]);

            return [
                'ok' => false,
                'request_ref' => null,
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{ok: bool, status: string, message: string, raw?: mixed}
     */
    public function status(string $requestRef): array
    {
        $cfg = IntegrationSettings::provider('payin');
        $key = trim((string) ($cfg['api_key'] ?? ''));
        $secret = trim((string) ($cfg['api_secret'] ?? ''));

        if (str_starts_with($requestRef, 'STUB-') || $key === '' || $secret === '') {
            return [
                'ok' => true,
                'status' => 'processing',
                'message' => 'Stub waiting for confirmation',
                'stub' => true,
            ];
        }

        $mode = $cfg['mode'] ?? 'sandbox';
        $base = $mode === 'live'
            ? 'https://api.payin.co.tz/api/v1'
            : 'https://api.sandbox.payin.co.tz/api/v1';

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $key,
                'X-API-Secret' => $secret,
                'Accept' => 'application/json',
            ])->timeout(15)->get($base.'/status/'.$requestRef);

            $json = $response->json() ?? [];

            return [
                'ok' => $response->successful(),
                'status' => (string) ($json['status'] ?? 'processing'),
                'message' => (string) ($json['message'] ?? ''),
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{ok: bool, message: string, mode: string, configured: bool}
     */
    public function health(): array
    {
        $cfg = IntegrationSettings::provider('payin');
        $configured = trim((string) ($cfg['api_key'] ?? '')) !== ''
            && trim((string) ($cfg['api_secret'] ?? '')) !== '';

        return [
            'ok' => (bool) ($cfg['enabled'] ?? false),
            'configured' => $configured,
            'mode' => (string) ($cfg['mode'] ?? 'sandbox'),
            'message' => $configured
                ? 'PayIn credentials present ('.($cfg['mode'] ?? 'sandbox').')'
                : 'PayIn keys missing — stub mode active for UI tests',
        ];
    }
}
