<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Membership;
use App\Models\PointTransaction;
use App\Models\Redemption;
use App\Models\Reward;
use App\Models\Shop;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'role' => User::ROLE_CUSTOMER,
            'phone_verified_at' => null,
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
    ): Visit {
        if (! $staff->canUseTill()) {
            throw ValidationException::withMessages(['staff' => 'You are not allowed to use the till.']);
        }

        $business = $staff->workplace();

        if (! $business || $shop->business_id !== $business->id) {
            throw ValidationException::withMessages(['shop' => 'This shop does not belong to your business.']);
        }

        if ($amountSpent <= 0) {
            throw ValidationException::withMessages(['amount_spent' => 'Enter the amount spent or ordered.']);
        }

        return DB::transaction(function () use ($staff, $shop, $customer, $amountSpent, $rewardId, $receiptRef, $channel, $business) {
            $membership = $this->memberships->join($business, $customer);
            $campaign = $this->findEarnCampaign($shop);
            $pointsEarned = $campaign ? $campaign->pointsForSpend($amountSpent) : 0;

            // Birthday bonus if campaign exists and today is birthday week
            $birthdayCampaign = $this->findBirthdayCampaign($business);
            if ($birthdayCampaign && $customer->birth_date && $customer->birth_date->isBirthday()) {
                $pointsEarned += $birthdayCampaign->bonus_points;
            }

            // Welcome bonus on first visit to this business
            $welcomeCampaign = $this->findWelcomeCampaign($business);
            if ($welcomeCampaign && $membership->visits()->doesntExist()) {
                $pointsEarned += $welcomeCampaign->bonus_points;
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
                    throw ValidationException::withMessages(['reward_id' => 'This reward is not available.']);
                }

                if ($membership->points_balance < $reward->points_cost) {
                    throw ValidationException::withMessages(['reward_id' => 'Customer does not have enough points yet.']);
                }

                $pointsRedeemed = $reward->points_cost;
                $discount = $reward->discountForAmount($amountSpent);
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
            ]);

            if ($pointsRedeemed > 0 && $reward) {
                $this->points->redeem(
                    $membership,
                    $pointsRedeemed,
                    $staff,
                    $visit,
                    "Applied reward: {$reward->name}"
                );

                if ($reward->stock !== null) {
                    $reward->decrement('stock');
                }

                Redemption::create([
                    'reward_id' => $reward->id,
                    'membership_id' => $membership->id,
                    'customer_id' => $customer->id,
                    'visit_id' => $visit->id,
                    'recorded_by' => $staff->id,
                    'points_spent' => $pointsRedeemed,
                    'discount_amount' => $discount,
                    'status' => 'applied',
                ]);

                $membership->refresh();
            }

            if ($pointsEarned > 0) {
                $this->points->earn(
                    $membership,
                    $pointsEarned,
                    $staff,
                    $visit,
                    "Sale at {$shop->name} · ".number_format($amountSpent, 0).' '.$business->currency
                );
            }

            return $visit->fresh(['customer', 'shop', 'campaign', 'reward', 'membership']);
        });
    }

    private function findEarnCampaign(Shop $shop): ?Campaign
    {
        return Campaign::query()
            ->active()
            ->where('business_id', $shop->business_id)
            ->where('type', Campaign::TYPE_EARN)
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
