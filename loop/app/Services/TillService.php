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
        ?string $receiptRef = null,
        string $channel = 'in_store',
        bool $applyPointsAsPayment = false,
        ?int $pointsToSpend = null,
        bool $includesFeaturedProduct = false,
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

        return DB::transaction(function () use ($staff, $shop, $customer, $amountSpent, $receiptRef, $channel, $business, $applyPointsAsPayment, $pointsToSpend, $includesFeaturedProduct, $limits) {
            $existingMembership = Membership::query()
                ->where('business_id', $business->id)
                ->where('customer_id', $customer->id)
                ->exists();

            if (! $existingMembership && ! $limits->canAcceptMember($business)) {
                throw ValidationException::withMessages(['plan' => $limits->memberLimitMessage($business)]);
            }

            $membership = $this->memberships->join($business, $customer, $shop);
            if (! $business->hasRedeemableOffer()) {
                \App\Support\DefaultOffer::ensure($business);
                $business = $business->fresh();
            }
            $campaign = $this->findEarnCampaign($shop);
            $canEarn = $business->hasRedeemableOffer();
            $basePoints = ($campaign && $canEarn) ? $campaign->pointsForSpend($amountSpent) : 0;
            $pointsEarned = $basePoints;
            $bonuses = [];

            if (! $canEarn && $campaign) {
                $bonuses[] = __('loop.earn_paused_no_offer_note');
            }

            if ($basePoints > 0) {
                $bonuses[] = __('loop.bonus_from_purchase', ['points' => $basePoints]);
            }

            if (
                $canEarn
                && $includesFeaturedProduct
                && $campaign
                && $campaign->type === 'product_push'
                && filled($campaign->featured_product_name)
                && (int) $campaign->bonus_points > 0
            ) {
                $pointsEarned += (int) $campaign->bonus_points;
                $bonuses[] = __('loop.bonus_from_featured', [
                    'product' => $campaign->featured_product_name,
                    'points' => $campaign->bonus_points,
                ]);
            }

            $birthdayCampaign = $canEarn ? $this->findBirthdayCampaign($business) : null;
            if ($birthdayCampaign && $customer->birth_month && $customer->birth_day
                && (int) $customer->birth_month === (int) now()->month
                && (int) $customer->birth_day === (int) now()->day) {
                $pointsEarned += $birthdayCampaign->bonus_points;
                $bonuses[] = __('loop.bonus_from_birthday', ['points' => $birthdayCampaign->bonus_points]);
            }

            $welcomeCampaign = $canEarn ? $this->findWelcomeCampaign($business) : null;
            $isFirstVisit = ! Visit::query()
                ->where('membership_id', $membership->id)
                ->exists();
            if ($welcomeCampaign && $isFirstVisit) {
                $pointsEarned += $welcomeCampaign->bonus_points;
                $bonuses[] = __('loop.bonus_from_welcome', ['points' => $welcomeCampaign->bonus_points]);
            }

            $streakBonus = $canEarn
                ? $this->applyStreakBonuses($business, $membership, $shop)
                : ['points' => 0, 'labels' => []];
            if ($streakBonus['points'] > 0) {
                $pointsEarned += $streakBonus['points'];
                $bonuses = array_merge($bonuses, $streakBonus['labels']);
            }

            $pointsRedeemed = 0;
            $discount = 0.0;

            if ($applyPointsAsPayment && $pointsToSpend) {
                if (! $business->payWithPointsEnabled()) {
                    throw ValidationException::withMessages(['pay_with_points' => __('loop.pay_with_points_disabled')]);
                }

                $membershipFresh = Membership::query()->findOrFail($membership->id);
                if ($membershipFresh->points_balance < $pointsToSpend) {
                    throw ValidationException::withMessages(['points_to_spend' => 'Not enough points.']);
                }

                $rate = $business->payCurrencyPerPoint();
                $maxPercent = $business->payPointsMaxPercent();
                $maxCurrency = $amountSpent > 0
                    ? round($amountSpent * ($maxPercent / 100), 2)
                    : PHP_FLOAT_MAX;
                $requestedCurrency = round($pointsToSpend * $rate, 2);

                if ($amountSpent > 0 && $requestedCurrency > $maxCurrency + 0.009) {
                    throw ValidationException::withMessages([
                        'points_to_spend' => __('loop.pay_with_points_max_error', [
                            'percent' => $maxPercent,
                            'currency' => $business->currency,
                            'amount' => number_format($maxCurrency, 0),
                        ]),
                    ]);
                }

                $discount = min($requestedCurrency, $maxCurrency === PHP_FLOAT_MAX ? $requestedCurrency : $maxCurrency);
                $pointsRedeemed = $pointsToSpend;
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
                'reward_id' => null,
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
                    __('loop.paid_with_points')
                );
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
     * Redeem an offer without requiring a sale. Points come from one business-wide balance.
     */
    public function redeemOffer(
        User $staff,
        Shop $shop,
        User $customer,
        int $rewardId,
        ?string $notes = null,
    ): Redemption {
        if (! $staff->canUseTill()) {
            throw ValidationException::withMessages(['staff' => 'You are not allowed to redeem offers.']);
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

        return DB::transaction(function () use ($staff, $shop, $customer, $rewardId, $notes, $business) {
            $membership = $this->memberships->join($business, $customer, $shop);
            $reward = Reward::query()
                ->where('business_id', $business->id)
                ->whereKey($rewardId)
                ->firstOrFail();

            if (! $reward->isAvailable()) {
                throw ValidationException::withMessages([
                    'reward_id' => $reward->stock === 0
                        ? __('loop.offer_finished_till')
                        : __('loop.offer_not_available'),
                ]);
            }

            $membershipFresh = Membership::query()->lockForUpdate()->findOrFail($membership->id);
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

            $note = trim((string) $notes);
            if ($note === '') {
                $note = __('loop.redemption_default_note', [
                    'offer' => $reward->name,
                    'label' => $reward->label(),
                ]);
            }

            $this->points->redeem(
                $membershipFresh,
                $reward->points_cost,
                $staff,
                null,
                __('loop.applied_offer', ['name' => $reward->name])
            );

            if ($reward->stock !== null) {
                $reward->decrement('stock');
            }

            return Redemption::create([
                'reward_id' => $reward->id,
                'membership_id' => $membership->id,
                'customer_id' => $customer->id,
                'visit_id' => null,
                'shop_id' => $shop->id,
                'recorded_by' => $staff->id,
                'points_spent' => $reward->points_cost,
                'discount_amount' => 0,
                'status' => 'applied',
                'notes' => $note,
            ])->fresh(['reward', 'customer', 'shop', 'membership']);
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
