<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Membership;
use App\Models\Redemption;
use App\Models\Reward;
use App\Models\Shop;
use App\Models\User;
use App\Models\Visit;
use App\Support\Countries;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TillService
{
    public function __construct(
        private readonly MembershipService $memberships,
        private readonly PointsService $points,
    ) {}

    public function findCustomer(string $countryCode, string $phone): ?User
    {
        return User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->where('country_code', $countryCode)
            ->where('phone', $phone)
            ->first();
    }

    public function registerCustomer(array $data): User
    {
        $existing = $this->findCustomer($data['country_code'], $data['phone']);

        if ($existing) {
            return $existing;
        }

        return User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'country_code' => $data['country_code'],
            'country' => Countries::fromDial($data['country_code']),
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'birth_month' => $data['birth_month'] ?? null,
            'birth_day' => $data['birth_day'] ?? null,
            'role' => User::ROLE_CUSTOMER,
            'phone_verified_at' => null,
            'profile_completed' => false,
            'is_active' => true,
        ]);
    }

    public function recordSale(
        User $staff,
        Shop $shop,
        User $customer,
        float $amountSpent,
        ?int $rewardId = null,
        ?string $receiptRef = null,
        string $channel = 'in_store',
        bool $applyPointsAsPayment = false,
        ?int $pointsToSpend = null,
    ): Visit {
        if (! $staff->canUseTill()) {
            throw ValidationException::withMessages(['staff' => 'You are not allowed to record sales.']);
        }

        $business = $staff->workplace();

        if (! $business || $shop->business_id !== $business->id) {
            throw ValidationException::withMessages(['shop' => 'This shop does not belong to your business.']);
        }

        $limits = app(PlanLimitService::class);
        $limits->syncTrialStatus($business->fresh());
        $business = $business->fresh();

        if (! $limits->canUseTill($business)) {
            throw ValidationException::withMessages(['plan' => $limits->trialExpiredMessage()]);
        }

        if (! $limits->canRecordVisit($business)) {
            throw ValidationException::withMessages(['plan' => $limits->visitLimitMessage($business)]);
        }

        if ($amountSpent <= 0 && ! $applyPointsAsPayment) {
            throw ValidationException::withMessages(['amount_spent' => 'Enter the amount spent or ordered.']);
        }

        return DB::transaction(function () use ($staff, $shop, $customer, $amountSpent, $rewardId, $receiptRef, $channel, $business, $applyPointsAsPayment, $pointsToSpend, $limits) {
            $existingMembership = Membership::query()
                ->where('business_id', $business->id)
                ->where('customer_id', $customer->id)
                ->exists();

            if (! $existingMembership && ! $limits->canAcceptMember($business)) {
                throw ValidationException::withMessages(['plan' => $limits->memberLimitMessage($business)]);
            }

            $membership = $this->memberships->join($business, $customer, $shop);
            $campaign = $this->findEarnCampaign($shop);
            $basePoints = $campaign ? $campaign->pointsForSpend($amountSpent) : 0;
            $pointsEarned = $basePoints;
            $bonuses = [];

            if ($basePoints > 0) {
                $bonuses[] = __('loop.bonus_from_purchase', ['points' => $basePoints]);
            }

            $birthdayCampaign = $this->findBirthdayCampaign($business);
            if ($birthdayCampaign && $customer->birth_month && $customer->birth_day
                && (int) $customer->birth_month === (int) now()->month
                && (int) $customer->birth_day === (int) now()->day) {
                $pointsEarned += $birthdayCampaign->bonus_points;
                $bonuses[] = __('loop.bonus_from_birthday', ['points' => $birthdayCampaign->bonus_points]);
            }

            $welcomeCampaign = $this->findWelcomeCampaign($business);
            $isFirstVisit = ! Visit::query()
                ->where('membership_id', $membership->id)
                ->exists();
            if ($welcomeCampaign && $isFirstVisit) {
                $pointsEarned += $welcomeCampaign->bonus_points;
                $bonuses[] = __('loop.bonus_from_welcome', ['points' => $welcomeCampaign->bonus_points]);
            }

            $streakBonus = $this->applyStreakBonuses($business, $membership, $shop);
            if ($streakBonus['points'] > 0) {
                $pointsEarned += $streakBonus['points'];
                $bonuses = array_merge($bonuses, $streakBonus['labels']);
            }

            $reward = null;
            $pointsRedeemed = 0;
            $discount = 0.0;

            if ($rewardId) {
                $reward = Reward::query()
                    ->where('business_id', $business->id)
                    ->whereKey($rewardId)
                    ->firstOrFail();

                if (! $reward->isAvailable()) {
                    throw ValidationException::withMessages(['reward_id' => 'This offer is not available.']);
                }

                $membershipFresh = Membership::query()->findOrFail($membership->id);
                if ($membershipFresh->points_balance < $reward->points_cost) {
                    throw ValidationException::withMessages(['reward_id' => 'Customer does not have enough points yet.']);
                }

                if ($reward->max_redemptions_per_member) {
                    $used = Redemption::query()
                        ->where('reward_id', $reward->id)
                        ->where('membership_id', $membership->id)
                        ->count();
                    if ($used >= $reward->max_redemptions_per_member) {
                        throw ValidationException::withMessages(['reward_id' => __('loop.offer_max_reached')]);
                    }
                }

                $pointsRedeemed = $reward->points_cost;
                $discount = $reward->discountForAmount($amountSpent);
            }

            if ($applyPointsAsPayment && $pointsToSpend) {
                $membershipFresh = Membership::query()->findOrFail($membership->id);
                $needed = $pointsRedeemed + $pointsToSpend;
                if ($membershipFresh->points_balance < $needed) {
                    throw ValidationException::withMessages(['points_to_spend' => 'Not enough points.']);
                }
                $earn = $this->findEarnCampaign($shop);
                if ($earn && $earn->points_per_step > 0 && $earn->spend_step > 0) {
                    $discount += ($pointsToSpend / $earn->points_per_step) * $earn->spend_step;
                }
                $pointsRedeemed += $pointsToSpend;
            }

            $visit = Visit::create([
                'business_id' => $business->id,
                'shop_id' => $shop->id,
                'customer_id' => $customer->id,
                'membership_id' => $membership->id,
                'campaign_id' => $campaign?->id,
                'recorded_by' => $staff->id,
                'amount_spent' => $amountSpent,
                'points_earned' => $pointsEarned,
                'reward_id' => $reward?->id,
                'points_redeemed' => $pointsRedeemed,
                'discount_amount' => $discount,
                'receipt_ref' => $receiptRef,
                'channel' => $channel,
                'notes' => $bonuses !== [] ? implode(' · ', $bonuses) : null,
            ]);

            if ($pointsRedeemed > 0) {
                $this->points->redeem(
                    $membership,
                    $pointsRedeemed,
                    $staff,
                    $visit,
                    $reward ? __('loop.applied_offer', ['name' => $reward->name]) : __('loop.paid_with_points')
                );

                if ($reward && $reward->stock !== null) {
                    $reward->decrement('stock');
                }

                if ($reward) {
                    Redemption::create([
                        'reward_id' => $reward->id,
                        'membership_id' => $membership->id,
                        'customer_id' => $customer->id,
                        'visit_id' => $visit->id,
                        'recorded_by' => $staff->id,
                        'points_spent' => $reward->points_cost,
                        'discount_amount' => $discount,
                        'status' => 'applied',
                    ]);
                }

                $membership->refresh();
            }

            if ($pointsEarned > 0) {
                $earnLabel = $bonuses !== []
                    ? implode(' · ', $bonuses)
                    : __('loop.sale_at_shop', ['shop' => $shop->name]);

                $this->points->earn(
                    $membership,
                    $pointsEarned,
                    $staff,
                    $visit,
                    $earnLabel
                );
            }

            return $visit->fresh(['customer', 'shop', 'campaign', 'reward', 'membership']);
        });
    }

    /**
     * @return array{points: int, labels: list<string>}
     */
    private function applyStreakBonuses(Business $business, Membership $membership, Shop $shop): array
    {
        $points = 0;
        $labels = [];

        $streaks = Campaign::query()
            ->active()
            ->where('business_id', $business->id)
            ->where('type', Campaign::TYPE_STREAK)
            ->get();

        foreach ($streaks as $streak) {
            $target = max(1, (int) ($streak->streak_target ?: 3));
            $period = $streak->streak_period === 'month' ? 'month' : 'week';
            $from = $period === 'month' ? now()->startOfMonth() : now()->startOfWeek();

            $visitCount = Visit::query()
                ->where('membership_id', $membership->id)
                ->where('created_at', '>=', $from)
                ->count() + 1; // include this sale

            if ($visitCount === $target) {
                $alreadyAwarded = Visit::query()
                    ->where('membership_id', $membership->id)
                    ->where('created_at', '>=', $from)
                    ->where('notes', 'like', '%'.$streak->displayName().'%')
                    ->exists();

                if (! $alreadyAwarded) {
                    $points += $streak->bonus_points;
                    $labels[] = __('loop.bonus_from_streak', [
                        'points' => $streak->bonus_points,
                        'target' => $target,
                        'period' => __('loop.streak_period_'.$period),
                    ]);
                }
            }
        }

        return ['points' => $points, 'labels' => $labels];
    }

    private function findEarnCampaign(Shop $shop): ?Campaign
    {
        return Campaign::query()
            ->active()
            ->where('business_id', $shop->business_id)
            ->whereIn('type', [Campaign::TYPE_EARN, 'product_push'])
            ->where(function ($query) use ($shop) {
                $query->whereDoesntHave('shops')
                    ->orWhereHas('shops', fn ($shops) => $shops->where('shops.id', $shop->id));
            })
            ->orderByDesc('points_per_step')
            ->first();
    }

    private function findBirthdayCampaign(Business $business): ?Campaign
    {
        return Campaign::query()
            ->active()
            ->where('business_id', $business->id)
            ->where('type', Campaign::TYPE_BIRTHDAY)
            ->first();
    }

    private function findWelcomeCampaign(Business $business): ?Campaign
    {
        return Campaign::query()
            ->active()
            ->where('business_id', $business->id)
            ->where('type', Campaign::TYPE_WELCOME)
            ->first();
    }
}
