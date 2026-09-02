<?php

namespace App\Http\Controllers;

use App\Support\MobileNav;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MoreController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && MobileNav::enabled($user), 403);

        return view('more.index', [
            'groups' => MobileNav::moreGroups($user),
            'board' => MobileNav::board($user),
        ]);
    }
}
