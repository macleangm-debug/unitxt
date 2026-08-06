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

        Log::info('Loop OTP', [
            'phone' => $countryCode.$phone,
            'code' => $code,
        ]);

        return $otp;
    }

    public function verifyCode(string $countryCode, string $phone, string $code): void
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
                'code' => __('That code is invalid or expired.'),
            ]);
        }

        $otp->update(['consumed_at' => now()]);
    }

    public function findCustomer(string $countryCode, string $phone): ?User
    {
        return User::query()
            ->where('country_code', $countryCode)
            ->where('phone', Countries::normalizePhone($phone))
            ->where('role', User::ROLE_CUSTOMER)
            ->first();
    }

    public function verify(string $countryCode, string $phone, string $code): User
    {
        $this->verifyCode($countryCode, $phone, $code);

        $user = $this->findCustomer($countryCode, $phone);

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => 'complete_profile',
            ]);
        }

        $user->forceFill([
            'phone_verified_at' => now(),
            'is_active' => true,
        ])->save();

        return $user;
    }
}
