<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Visit;
use App\Support\GrowthSettings;
use Illuminate\Support\Carbon;

class BusinessInsightService
{
    /**
     * @return list<array{key: string, tone: string, title_key: string, body_key: string, params: array<string, mixed>, cta_key: string, url: string}>
     */
    public function notificationSpecs(Business $business): array
    {
        $settings = GrowthSettings::settings();
        $specs = [];

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
            $specs[] = [
                'key' => 'campaign_up',
                'tone' => 'mint',
                'title_key' => 'loop.insight_campaign_up_title',
                'body_key' => 'loop.insight_campaign_up_body',
                'params' => ['pct' => round($campaignDelta)],
                'cta_key' => 'loop.view_campaigns',
                'url' => route('campaigns.index'),
            ];
        } elseif ($settings['banner_show_campaign_down'] && $campaignDelta <= -$campaignThreshold && $lastWeek['visits'] >= 3) {
            $specs[] = [
                'key' => 'campaign_down',
                'tone' => 'coral',
                'title_key' => 'loop.insight_campaign_down_title',
                'body_key' => 'loop.insight_campaign_down_body',
                'params' => ['pct' => abs(round($campaignDelta))],
                'cta_key' => 'loop.add_offer',
                'url' => route('rewards.create'),
            ];
        }

        if ($settings['banner_show_retention_up'] && $retentionDelta >= $retentionThreshold && $retentionNow > 0) {
            $specs[] = [
                'key' => 'retention_up',
                'tone' => 'mint',
                'title_key' => 'loop.insight_retention_up_title',
                'body_key' => 'loop.insight_retention_up_body',
                'params' => ['rate' => round($retentionNow)],
                'cta_key' => 'loop.view_customers',
                'url' => route('customers.index'),
            ];
        } elseif ($settings['banner_show_retention_down'] && $retentionDelta <= -$retentionThreshold) {
            $specs[] = [
                'key' => 'retention_down',
                'tone' => 'coral',
                'title_key' => 'loop.insight_retention_down_title',
                'body_key' => 'loop.insight_retention_down_body',
                'params' => [],
                'cta_key' => 'loop.add_offer',
                'url' => route('rewards.create'),
            ];
        }

        if ($settings['banner_show_add_offers_cta'] && $offerCount === 0) {
            array_unshift($specs, [
                'key' => 'need_offers',
                'tone' => 'ink',
                'title_key' => 'loop.insight_need_offers_title',
                'body_key' => 'loop.insight_need_offers_body',
                'params' => [],
                'cta_key' => 'loop.add_offer',
                'url' => route('rewards.create'),
            ]);
        }

        $minRaffle = GrowthSettings::raffleMinMembers();
        if (
            $settings['banner_show_raffle_unlock']
            && $memberCount >= $minRaffle
            && $business->raffles()->doesntExist()
        ) {
            $specs[] = [
                'key' => 'raffle_ready',
                'tone' => 'ink',
                'title_key' => 'loop.insight_raffle_ready_title',
                'body_key' => 'loop.insight_raffle_ready_body',
                'params' => ['count' => $memberCount],
                'cta_key' => 'loop.create_raffle',
                'url' => route('raffles.create'),
            ];
        }

        if ($settings['banner_show_member_milestones']) {
            foreach ($settings['banner_member_milestones'] as $milestone) {
                $milestone = (int) $milestone;
                if ($milestone > 0 && $memberCount >= $milestone && $memberCount < $milestone + 5) {
                    array_unshift($specs, [
                        'key' => 'members_'.$milestone,
                        'tone' => 'mint',
                        'title_key' => 'loop.insight_members_title',
                        'body_key' => 'loop.insight_members_body',
                        'params' => ['count' => $milestone],
                        'cta_key' => 'loop.open_content_studio',
                        'url' => route('content-studio.index'),
                    ]);
                    break;
                }
            }
        }

        return array_slice($specs, 0, (int) $settings['banner_max_count']);
    }

    /**
     * @return list<array{key: string, tone: string, title: string, body: string, cta: string, url: string}>
     */
    public function heroBanners(Business $business): array
    {
        return array_map(function (array $spec) {
            return [
                'key' => $spec['key'],
                'tone' => $spec['tone'],
                'title' => __($spec['title_key'], $spec['params'] ?? []),
                'body' => __($spec['body_key'], $spec['params'] ?? []),
                'cta' => __($spec['cta_key'], $spec['params'] ?? []),
                'url' => $spec['url'],
            ];
        }, $this->notificationSpecs($business));
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
