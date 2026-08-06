<?php

namespace App\Services;

use App\Models\PhoneOtp;
use App\Models\User;
use App\Support\Countries;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function send(string $countryCode, string $phone): PhoneOtp
    {
        $phone = Countries::normalizePhone($phone);

        PhoneOtp::query()
            ->where('country_code', $countryCode)
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = app()->environment('local', 'testing') ? '123456' : (string) random_int(100000, 999999);

        $otp = PhoneOtp::create([
            'country_code' => $countryCode,
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Placeholder for SMS/WhatsApp provider integration.
        Log::info('Loop OTP', [
            'phone' => $countryCode.$phone,
            'code' => $code,
        ]);

        return $otp;
    }

    public function verify(string $countryCode, string $phone, string $code): User
    {
        $phone = Countries::normalizePhone($phone);

        $otp = PhoneOtp::query()
            ->where('country_code', $countryCode)
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $otp || ! $otp->isValid($code)) {
            throw ValidationException::withMessages([
                'code' => 'That code is invalid or expired.',
            ]);
        }

        $otp->update(['consumed_at' => now()]);

        $user = User::query()
            ->where('country_code', $countryCode)
            ->where('phone', $phone)
            ->where('role', User::ROLE_CUSTOMER)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => 'No Loop customer found for this number. Ask a shop to add you on your next visit, or browse campaigns first.',
            ]);
        }

        $user->forceFill([
            'phone_verified_at' => now(),
            'is_active' => true,
        ])->save();

        return $user;
    }
}
