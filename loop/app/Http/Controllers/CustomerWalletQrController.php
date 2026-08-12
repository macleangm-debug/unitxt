<?php

namespace App\Http\Controllers;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

class CustomerWalletQrController extends Controller
{
    /**
     * QR payload the till can scan to look up this customer (phone).
     * Format: Loop till deep-link with dial + national number.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->isCustomer(), 403);

        $country = strtoupper((string) ($user->country ?: 'TZ'));
        $dial = ltrim(\App\Support\Countries::dial($country), '+');
        $phone = preg_replace('/\D+/', '', (string) $user->phone);
        $payload = URL::route('till.index', ['scan' => $dial.'|'.$phone], absolute: true);

        $renderer = new ImageRenderer(
            new RendererStyle(320, 1),
            new SvgImageBackEnd
        );
        $svg = (new Writer($renderer))->writeString($payload);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
