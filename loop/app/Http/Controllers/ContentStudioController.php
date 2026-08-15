<?php

namespace App\Http\Controllers;

use App\Support\ContentStudio;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentStudioController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $business->load([
            'rewards' => fn ($q) => $q->where('is_active', true)->orderBy('points_cost'),
            'campaigns' => fn ($q) => $q->where('is_active', true)->latest(),
        ]);

        return view('content-studio.index', [
            'business' => $business,
            'designs' => ContentStudio::designs(),
            'copies' => ContentStudio::copies($business),
            'sizes' => ContentStudio::sizes(),
            'modes' => ContentStudio::modes(),
            'textColors' => ContentStudio::textColors(),
            'locale' => app()->getLocale(),
        ]);
    }
}
