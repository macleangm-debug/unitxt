<?php

namespace App\Http\Controllers;

use App\Support\HelpFaqs;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpController extends Controller
{
    public function __invoke(Request $request): View
    {
        $q = strtolower(trim((string) $request->query('q', '')));
        $groups = HelpFaqs::groups();

        if ($q !== '') {
            $groups = collect($groups)
                ->map(function (array $group) use ($q) {
                    $group['items'] = array_values(array_filter(
                        $group['items'],
                        fn (array $item) => str_contains(strtolower($item['q'].' '.$item['a']), $q)
                    ));

                    return $group;
                })
                ->filter(fn (array $group) => $group['items'] !== [])
                ->values()
                ->all();
        }

        $user = $request->user();

        return view('help.index', [
            'groups' => $groups,
            'search' => $q,
            'isCustomer' => $user?->isCustomer() ?? false,
            'inApp' => (bool) $user,
        ]);
    }
}
