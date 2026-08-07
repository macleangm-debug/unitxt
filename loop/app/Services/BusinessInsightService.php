<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Visit;
use App\Support\GrowthSettings;
use Illuminate\Support\Carbon;

class BusinessInsightService
{
    /**
     * @return list<array{key: string, tone: string, title: string, body: string, cta: string, url: string}>
     */
    public function heroBanners(Business $business): array
    {
        $settings = GrowthSettings::settings();
        $banners = [];

        $memberCount = $business->uniqueMemberCount();
        $offerCount = $business->rewards()->where('is_active', true)->count();
        $campaignThreshold = (float) $settings['campaign_delta_threshold_pct'];
        $retentionThreshold = (float) $settings['retention_delta_threshold_pct'];

        $thisWeek = $this->periodStats($business, now()->startOfWeek(), now());
        $lastWeek = $this->periodStats($business, now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek());

        $campaignDelta = $lastWeek['visits'] > 0
            ? (($thisWeek['visits'] - $lastWeek['visits']) / $lastWeek['visits']) * 100
            : ($thisWeek['visits'] > 0 ? 100 : 0);

        $retentionNow = $this->retentionRate($business, now()->subDays(30), now());
        $retentionPrev = $this->retentionRate($business, now()->subDays(60), now()->subDays(30));
        $retentionDelta = $retentionNow - $retentionPrev;

        if ($settings['banner_show_campaign_up'] && $campaignDelta >= $campaignThreshold && $thisWeek['visits'] >= 3) {
            $banners[] = [
                'key' => 'campaign_up',
                'tone' => 'mint',
                'title' => __('loop.insight_campaign_up_title'),
                'body' => __('loop.insight_campaign_up_body', ['pct' => round($campaignDelta)]),
                'cta' => __('loop.view_campaigns'),
                'url' => route('campaigns.index'),
            ];
        } elseif ($settings['banner_show_campaign_down'] && $campaignDelta <= -$campaignThreshold && $lastWeek['visits'] >= 3) {
            $banners[] = [
                'key' => 'campaign_down',
                'tone' => 'coral',
                'title' => __('loop.insight_campaign_down_title'),
                'body' => __('loop.insight_campaign_down_body', ['pct' => abs(round($campaignDelta))]),
                'cta' => __('loop.add_offer'),
                'url' => route('rewards.create'),
            ];
        }

        if ($settings['banner_show_retention_up'] && $retentionDelta >= $retentionThreshold && $retentionNow > 0) {
            $banners[] = [
                'key' => 'retention_up',
                'tone' => 'mint',
                'title' => __('loop.insight_retention_up_title'),
                'body' => __('loop.insight_retention_up_body', ['rate' => round($retentionNow)]),
                'cta' => __('loop.view_customers'),
                'url' => route('customers.index'),
            ];
        } elseif ($settings['banner_show_retention_down'] && $retentionDelta <= -$retentionThreshold) {
            $banners[] = [
                'key' => 'retention_down',
                'tone' => 'coral',
                'title' => __('loop.insight_retention_down_title'),
                'body' => __('loop.insight_retention_down_body'),
                'cta' => __('loop.add_offer'),
                'url' => route('rewards.create'),
            ];
        }

        if ($settings['banner_show_add_offers_cta'] && $offerCount === 0) {
            array_unshift($banners, [
                'key' => 'need_offers',
                'tone' => 'ink',
                'title' => __('loop.insight_need_offers_title'),
                'body' => __('loop.insight_need_offers_body'),
                'cta' => __('loop.add_offer'),
                'url' => route('rewards.create'),
            ]);
        }

        $minRaffle = GrowthSettings::raffleMinMembers();
        if (
            $settings['banner_show_raffle_unlock']
            && $memberCount >= $minRaffle
            && $business->raffles()->doesntExist()
        ) {
            $banners[] = [
                'key' => 'raffle_ready',
                'tone' => 'ink',
                'title' => __('loop.insight_raffle_ready_title'),
                'body' => __('loop.insight_raffle_ready_body', ['count' => $memberCount]),
                'cta' => __('loop.create_raffle'),
                'url' => route('raffles.create'),
            ];
        }

        if ($settings['banner_show_member_milestones']) {
            foreach ($settings['banner_member_milestones'] as $milestone) {
                $milestone = (int) $milestone;
                if ($milestone > 0 && $memberCount >= $milestone && $memberCount < $milestone + 5) {
                    array_unshift($banners, [
                        'key' => 'members_'.$milestone,
                        'tone' => 'mint',
                        'title' => __('loop.insight_members_title', ['count' => $milestone]),
                        'body' => __('loop.insight_members_body', ['count' => $milestone]),
                        'cta' => __('loop.open_content_studio'),
                        'url' => route('content-studio.index'),
                    ]);
                    break;
                }
            }
        }

        return array_slice($banners, 0, (int) $settings['banner_max_count']);
    }

    /**
     * @return array{visits: int, spend: float, customers: int}
     */
    private function periodStats(Business $business, Carbon $from, Carbon $to): array
    {
        $q = Visit::query()
            ->where('business_id', $business->id)
            ->whereBetween('created_at', [$from, $to]);

        return [
            'visits' => (clone $q)->count(),
            'spend' => (float) (clone $q)->sum('amount_spent'),
            'customers' => (clone $q)->distinct('customer_id')->count('customer_id'),
        ];
    }

    private function retentionRate(Business $business, Carbon $from, Carbon $to): float
    {
        $customers = Visit::query()
            ->where('business_id', $business->id)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('customer_id, COUNT(*) as c')
            ->groupBy('customer_id')
            ->get();

        if ($customers->isEmpty()) {
            return 0;
        }

        $returning = $customers->where('c', '>', 1)->count();

        return ($returning / $customers->count()) * 100;
    }
}
