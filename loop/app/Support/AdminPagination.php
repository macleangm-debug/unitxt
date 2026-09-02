<?php

namespace App\Support;

use Illuminate\Http\Request;

class AdminPagination
{
    public const OPTIONS = [25, 50, 100];

    public static function perPage(Request $request, int $default = 25): int
    {
        $n = (int) $request->integer('per_page', $default);

        return in_array($n, self::OPTIONS, true) ? $n : $default;
    }
}
