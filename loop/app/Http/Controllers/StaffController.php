<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Confirm;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business, 403);

        return view('staff.index', [
            'business' => $business,
            'staff' => $business->frontDeskStaff()->latest()->get(),
            'countries' => Countries::OPTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $phone = Countries::normalizePhone($data['phone']);

        if (User::query()->where('country_code', $data['country_code'])->where('phone', $phone)->exists()) {
            return back()->withInput()->withErrors(['phone' => 'That phone is already registered on Loop.']);
        }

        User::create([
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

        return back()->with('confirm', Confirm::make(
            __('loop.staff_added_title'),
            __('loop.staff_added_body'),
            __('loop.done'),
            route('staff.index'),
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
}
