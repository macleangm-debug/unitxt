<?php

namespace App\Http\Controllers;

use App\Models\BusinessInvite;
use App\Support\Countries;
use App\Support\PlatformUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BusinessInviteController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isCustomer(), 403);

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:120'],
            'country_code' => ['nullable', 'string', 'max:8'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:255'],
            'share_via' => ['required', 'in:whatsapp,sms'],
        ]);

        $countryCode = $data['country_code'] ?? Countries::dial($request->user()->country ?? 'TZ');
        $phone = filled($data['phone'] ?? null) ? Countries::normalizePhone($data['phone']) : null;

        BusinessInvite::create([
            'customer_id' => $request->user()->id,
            'business_name' => $data['business_name'],
            'country_code' => $countryCode,
            'phone' => $phone,
            'city' => $data['city'] ?? $request->user()->city,
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ]);

        $link = PlatformUrl::route('business.register', [
            'scout' => $request->user()->id,
        ]);

        $message = __('loop.scout_share_text', [
            'name' => $data['business_name'],
            'customer' => $request->user()->name,
            'url' => $link,
        ]);

        if ($data['share_via'] === 'sms') {
            $smsPhone = $phone ? preg_replace('/\D+/', '', $countryCode.$phone) : null;

            return redirect()->away(PlatformUrl::smsShareUrl($message, $smsPhone ? '+'.$smsPhone : null));
        }

        $waDigits = null;
        if ($phone) {
            $waDigits = preg_replace('/\D+/', '', $countryCode.$phone);
        }

        return redirect()->away(PlatformUrl::whatsappShareUrl($message, $waDigits));
    }
}
