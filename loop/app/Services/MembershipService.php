<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Membership;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Str;

class MembershipService
{
    public function join(Business $business, User $customer, Shop $shop): Membership
    {
        return Membership::firstOrCreate(
            [
                'business_id' => $business->id,
                'customer_id' => $customer->id,
            ],
            [
                'shop_id' => $shop->id,
                'member_code' => 'LP-'.Str::upper(Str::random(8)),
                'joined_at' => now(),
            ]
        );
    }
}
