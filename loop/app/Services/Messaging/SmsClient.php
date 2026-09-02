<?php

namespace App\Services\Messaging;

use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmsClient
{
    /**
     * @return array{ok: bool, configured: bool, provider: string, message: string}
     */
    public function health(): array
    {
        $cfg = IntegrationSettings::settings()['messaging'] ?? [];
        $provider = (string) ($cfg['provider'] ?? 'stub');
        $configured = trim((string) ($cfg['api_key'] ?? '')) !== '';
        $enabled = ! empty($cfg['enabled']);

        return [
            'ok' => $enabled,
            'configured' => $configured,
            'provider' => $provider,
            'message' => $configured
                ? 'SMS credentials present ('.$provider.')'
                : 'SMS keys missing — stub mode active for tests',
        ];
    }

    /**
     * @param  list<string>  $phones
     * @return array{ok: bool, sent: int, message: string, stub?: bool, raw?: mixed}
     */
    public function send(string $sender, array $phones, string $body): array
    {
        $phones = array_values(array_unique(array_filter($phones)));
        $cfg = IntegrationSettings::settings()['messaging'] ?? [];
        $key = trim((string) ($cfg['api_key'] ?? ''));
        $provider = (string) ($cfg['provider'] ?? 'stub');

        if ($key === '' || $provider === 'stub') {
            return [
                'ok' => true,
                'sent' => count($phones),
                'message' => 'Stub SMS queued as '.$sender,
                'stub' => true,
                'ref' => 'SMS-STUB-'.Str::upper(Str::random(8)),
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$key,
                'Accept' => 'application/json',
            ])->timeout(20)->post((string) ($cfg['api_url'] ?? ''), [
                'from' => $sender,
                'to' => $phones,
                'text' => $body,
            ]);

            $json = $response->json() ?? [];
            if ($response->successful()) {
                return [
                    'ok' => true,
                    'sent' => count($phones),
                    'message' => (string) ($json['message'] ?? 'Queued'),
                    'raw' => $json,
                ];
            }

            return [
                'ok' => false,
                'sent' => 0,
                'message' => (string) ($json['message'] ?? 'SMS send failed'),
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            Log::warning('SMS send failed', ['error' => $e->getMessage()]);

            return [
                'ok' => false,
                'sent' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }
}
