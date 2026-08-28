<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Membership;
use App\Models\Raffle;
use App\Models\RaffleWinner;
use App\Models\User;
use App\Support\GrowthSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RaffleService
{
    public function eligibleCustomers(Business $business)
    {
        return Membership::query()
            ->where('business_id', $business->id)
            ->with('customer')
            ->get()
            ->unique('customer_id')
            ->values();
    }

    public function openWinsForCustomer(Business $business, int $customerId)
    {
        return RaffleWinner::query()
            ->with('raffle')
            ->where('customer_id', $customerId)
            ->whereIn('status', ['pending', 'contacted'])
            ->whereHas('raffle', fn ($q) => $q->where('business_id', $business->id))
            ->latest('id')
            ->get();
    }

    public function drawNext(Raffle $raffle, User $operator): RaffleWinner
    {
        if (! in_array($raffle->status, ['scheduled', 'live'], true)) {
            throw ValidationException::withMessages(['raffle' => __('loop.raffle_not_drawable')]);
        }

        if ($raffle->remainingWinnerSlots() <= 0) {
            throw ValidationException::withMessages(['raffle' => __('loop.raffle_full')]);
        }

        if (! $raffle->canDrawNow()) {
            throw ValidationException::withMessages(['raffle' => __('loop.raffle_draw_too_soon', [
                'date' => $raffle->nextDrawDate()->format('j M Y'),
            ])]);
        }

        return DB::transaction(function () use ($raffle, $operator) {
            $raffle->update(['status' => 'live']);

            $already = $raffle->winners()->pluck('customer_id')->all();
            $pool = $this->eligibleCustomers($raffle->business)
                ->reject(fn ($m) => in_array($m->customer_id, $already, true))
                ->values();

            if ($pool->isEmpty()) {
                throw ValidationException::withMessages(['raffle' => __('loop.raffle_no_eligible')]);
            }

            $pick = $pool->random();
            $order = $raffle->winners()->count() + 1;
            $claimDays = $raffle->claim_days ?: GrowthSettings::settings()['raffle_default_claim_days'];

            $winner = RaffleWinner::create([
                'raffle_id' => $raffle->id,
                'customer_id' => $pick->customer_id,
                'membership_id' => $pick->id,
                'draw_order' => $order,
                'status' => 'pending',
                'drawn_at' => now(),
                'claim_by' => now()->addDays($claimDays),
            ]);

            if ($raffle->remainingWinnerSlots() <= 0) {
                $raffle->update([
                    'status' => 'completed',
                    'drawn_at' => now(),
                ]);
            }

            return $winner->load('customer');
        });
    }

    public function markContacted(RaffleWinner $winner): void
    {
        if ($winner->status === 'pending') {
            $winner->update(['status' => 'contacted']);
        }
    }

    public function markClaimed(RaffleWinner $winner): void
    {
        $winner->update([
            'status' => 'claimed',
            'claimed_at' => now(),
        ]);
    }
}
