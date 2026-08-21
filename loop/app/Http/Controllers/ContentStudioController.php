<?php

namespace App\Http\Controllers;

use App\Support\ContentStudio;
use App\Support\FeatureFlags;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentStudioController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);
        if (! FeatureFlags::enabled('content_studio')) {
            return redirect()->route('settings')->with('status', __('loop.feature_paused_studio'));
        }

        $byTopic = ContentStudio::copiesByTopic($business);
        $topic = (string) $request->query('about', 'loop');
        if (! array_key_exists($topic, $byTopic)) {
            $topic = 'loop';
        }

        $copyKey = $byTopic[$topic][0]['key'] ?? (ContentStudio::copies()[0]['key'] ?? 'now_on_loop');
        $sourceId = $request->integer('id');
        if ($sourceId > 0) {
            $match = collect($byTopic[$topic])->firstWhere('source_id', $sourceId);
            if ($match) {
                $copyKey = $match['key'];
            }
        }

        return view('content-studio.index', [
            'business' => $business,
            'designs' => ContentStudio::designs(),
            'topics' => ContentStudio::topics(),
            'copiesByTopic' => $byTopic,
            'emptyHints' => [
                'offer' => ['title' => __('loop.studio_empty_offer_title'), 'body' => __('loop.studio_empty_offer_body'), 'cta' => __('loop.add_offer'), 'url' => route('rewards.create')],
                'campaign' => ['title' => __('loop.studio_empty_campaign_title'), 'body' => __('loop.studio_empty_campaign_body'), 'cta' => __('loop.view_campaigns'), 'url' => route('campaigns.create')],
                'raffle' => ['title' => __('loop.studio_empty_raffle_title'), 'body' => __('loop.studio_empty_raffle_body'), 'cta' => __('loop.create_raffle'), 'url' => route('raffles.create')],
            ],
            'initialTopic' => $topic,
            'initialCopyKey' => $copyKey,
            'locale' => app()->getLocale(),
        ]);
    }
}
