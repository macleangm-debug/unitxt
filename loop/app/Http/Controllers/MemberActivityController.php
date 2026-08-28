<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberActivityController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user?->isCustomer(), 403);

        $memberships = Membership::query()
            ->with('business')
            ->where('customer_id', $user->id)
            ->get()
            ->keyBy('id');

        $rows = PointTransaction::query()
            ->whereIn('membership_id', $memberships->keys())
            ->latest('id')
            ->limit(200)
            ->get();

        $grouped = [];
        $order = [];
        foreach ($rows as $tx) {
            $key = ($tx->visit_id ? 'v:'.$tx->visit_id : 't:'.$tx->id);
            if (! isset($grouped[$key])) {
                $grouped[$key] = (object) [
                    'points' => 0,
                    'created_at' => $tx->created_at,
                    'type' => $tx->type,
                    'visit_id' => $tx->visit_id,
                    'shop_name' => $memberships->get($tx->membership_id)?->business?->name ?? '',
                ];
                $order[] = $key;
            }
            $grouped[$key]->points += (int) $tx->points;
            if ($tx->type === PointTransaction::TYPE_REDEEM) {
                $grouped[$key]->type = PointTransaction::TYPE_REDEEM;
            }
        }

        $recent = collect($order)->map(fn (string $key) => $grouped[$key])->values();

        return view('members.activity', [
            'recent' => $recent,
        ]);
    }
}
