<?php

namespace App\Http\Controllers;

use App\Support\MarketingSettings;
use App\Support\Plans;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function __invoke(): View
    {
        return view('pricing', [
            'plans' => Plans::publicPlans(),
            'pricingBlurb' => MarketingSettings::pricingBlurb(),
            'heroTagline' => MarketingSettings::heroTagline(),
            'marketing' => MarketingSettings::settings(),
        ]);
    }
}
