<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Confirm;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StaffSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.staff-login', [
            'countries' => Countries::OPTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string'],
        ]);

        $phone = Countries::normalizePhone($data['phone']);

        $user = User::query()
            ->whereIn('role', [User::ROLE_OWNER, User::ROLE_FRONT_DESK, User::ROLE_ADMIN])
            ->where('country_code', $data['country_code'])
            ->where('phone', $phone)
            ->first();

        if (! $user || ! $user->is_active || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            return back()->withInput($request->only('country_code', 'phone'))->with('confirm', Confirm::error(
                __('loop.login_failed_title'),
                __('loop.login_failed_body'),
                __('loop.try_again'),
                route('staff.login'),
            ));
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
