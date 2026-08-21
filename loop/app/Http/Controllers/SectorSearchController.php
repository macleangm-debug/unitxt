<?php

namespace App\Http\Controllers;

use App\Models\SectorSearchMiss;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectorSearchController extends Controller
{
    public function miss(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        SectorSearchMiss::record($data['q']);

        return response()->json(['ok' => true]);
    }
}
