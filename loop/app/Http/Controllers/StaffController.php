<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use App\Support\Confirm;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business, 403);

        $shops = $business->shops()->where('is_active', true)->with('staff')->orderBy('name')->get();
        $staffByShop = [];
        foreach ($shops as $shop) {
            $desk = $shop->staff->first(fn (User $user) => $user->isFrontDesk() && $user->is_active);
            if ($desk) {
                $staffByShop[$shop->id] = $desk;
            }
        }

        return view('staff.index', [
            'business' => $business,
            'staff' => $business->frontDeskStaff()->with('assignedShops')->latest()->get(),
            'shops' => $shops,
            'staffByShop' => $staffByShop,
            'freeShopCount' => $shops->filter(fn ($shop) => empty($staffByShop[$shop->id]))->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $shopCount = $business->shops()->where('is_active', true)->count();
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', Password::defaults()],
            'shop_ids' => $shopCount > 1 ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'shop_ids.*' => ['integer', 'exists:shops,id'],
        ]);

        $phone = Countries::normalizePhone($data['phone']);

        if (User::query()->where('country_code', $data['country_code'])->where('phone', $phone)->exists()) {
            return back()->withInput()->withErrors(['phone' => 'That phone is already registered on Loop.']);
        }

        $shopIds = $this->ownedShopIds($business, $data['shop_ids'] ?? []);
        if ($shopIds === [] && $shopCount === 1) {
            $shopIds = $business->shops()->where('is_active', true)->pluck('id')->all();
        }

        $taken = $this->takenShopIds($shopIds);
        if ($taken->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'shop_ids' => __('loop.shop_already_has_staff', [
                    'shops' => $business->shops()->whereIn('id', $taken)->pluck('name')->join(', '),
                ]),
            ]);
        }

        $staff = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'country_code' => $data['country_code'],
            'phone' => $phone,
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_FRONT_DESK,
            'business_id' => $business->id,
            'must_change_password' => true,
            'phone_verified_at' => now(),
            'is_active' => true,
        ]);

        if ($shopIds !== []) {
            $staff->assignedShops()->sync($shopIds);
        }

        return back()->with('confirm', Confirm::make(
            __('loop.staff_added_title'),
            __('loop.staff_added_body'),
            __('loop.done'),
            route('staff.index'),
        ));
    }

    public function updateShops(Request $request, User $staff): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);
        abort_unless($staff->business_id === $business->id && $staff->isFrontDesk(), 404);

        $shopCount = $business->shops()->where('is_active', true)->count();
        $data = $request->validate([
            'shop_ids' => $shopCount > 1 ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'shop_ids.*' => ['integer', 'exists:shops,id'],
        ]);

        $shopIds = $this->ownedShopIds($business, $data['shop_ids'] ?? []);
        if ($shopIds === [] && $shopCount === 1) {
            $shopIds = $business->shops()->where('is_active', true)->pluck('id')->all();
        }

        $taken = $this->takenShopIds($shopIds, $staff->id);
        if ($taken->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'shop_ids' => __('loop.shop_already_has_staff', [
                    'shops' => $business->shops()->whereIn('id', $taken)->pluck('name')->join(', '),
                ]),
            ]);
        }

        $staff->assignedShops()->sync($shopIds);

        return back()->with('confirm', Confirm::make(
            __('loop.staff_shops_saved_title'),
            __('loop.staff_shops_saved_body'),
            __('loop.done'),
            route('staff.index'),
            false,
        ));
    }

    public function toggle(Request $request, User $staff): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);
        abort_unless($staff->business_id === $business->id && $staff->isFrontDesk(), 404);

        $staff->update(['is_active' => ! $staff->is_active]);

        return back()->with('confirm', Confirm::make(
            $staff->is_active ? __('loop.staff_enabled_title') : __('loop.staff_disabled_title'),
            $staff->is_active ? __('loop.staff_enabled_body') : __('loop.staff_disabled_body'),
            __('loop.done'),
            route('staff.index'),
            false,
        ));
    }

    /**
     * @param  array<int, mixed>  $shopIds
     * @return list<int>
     */
    private function ownedShopIds(Business $business, array $shopIds): array
    {
        return $business->shops()
            ->where('is_active', true)
            ->whereIn('id', $shopIds)
            ->pluck('id')
            ->all();
    }

    /**
     * @param  list<int>  $shopIds
     * @return Collection<int, int>
     */
    private function takenShopIds(array $shopIds, ?int $exceptUserId = null): Collection
    {
        $query = DB::table('shop_user')
            ->join('users', 'users.id', '=', 'shop_user.user_id')
            ->whereIn('shop_user.shop_id', $shopIds)
            ->where('users.role', User::ROLE_FRONT_DESK)
            ->where('users.is_active', true);

        if ($exceptUserId) {
            $query->where('users.id', '!=', $exceptUserId);
        }

        return $query->pluck('shop_user.shop_id');
    }
}
