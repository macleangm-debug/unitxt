<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PointsService
{
    public function earn(
        Membership $membership,
        int $points,
        ?User $recordedBy = null,
        ?Visit $visit = null,
        ?string $description = null,
    ): PointTransaction {
        if ($points <= 0) {
            throw new InvalidArgumentException('Points to earn must be greater than zero.');
        }

        return DB::transaction(function () use ($membership, $points, $recordedBy, $visit, $description) {
            $membership = Membership::query()->lockForUpdate()->findOrFail($membership->id);
            $membership->points_balance += $points;
            $membership->lifetime_points += $points;
            $membership->save();

            return PointTransaction::create([
                'membership_id' => $membership->id,
                'visit_id' => $visit?->id,
                'recorded_by' => $recordedBy?->id,
                'type' => PointTransaction::TYPE_EARN,
                'points' => $points,
                'balance_after' => $membership->points_balance,
                'description' => $description ?? 'Points earned',
            ]);
        });
    }

    public function redeem(
        Membership $membership,
        int $points,
        ?User $recordedBy = null,
        ?Visit $visit = null,
        ?string $description = null,
    ): PointTransaction {
        if ($points <= 0) {
            throw new InvalidArgumentException('Points to redeem must be greater than zero.');
        }

        return DB::transaction(function () use ($membership, $points, $recordedBy, $visit, $description) {
            $membership = Membership::query()->lockForUpdate()->findOrFail($membership->id);

            if ($membership->points_balance < $points) {
                throw new InvalidArgumentException('Insufficient points balance.');
            }

            $membership->points_balance -= $points;
            $membership->save();

            return PointTransaction::create([
                'membership_id' => $membership->id,
                'visit_id' => $visit?->id,
                'recorded_by' => $recordedBy?->id,
                'type' => PointTransaction::TYPE_REDEEM,
                'points' => -$points,
                'balance_after' => $membership->points_balance,
                'description' => $description ?? 'Points redeemed at till',
            ]);
        });
    }
}
