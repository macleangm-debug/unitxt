<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExceptionHit;
use App\Support\Confirm;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionHitController extends Controller
{
    public function index(): View
    {
        $hits = ExceptionHit::query()
            ->whereNull('resolved_at')
            ->orderByDesc('last_seen_at')
            ->limit(100)
            ->get();

        return view('admin.errors.index', [
            'hits' => $hits,
        ]);
    }

    public function show(ExceptionHit $hit): View
    {
        return view('admin.errors.show', [
            'hit' => $hit,
        ]);
    }

    public function resolve(ExceptionHit $hit): RedirectResponse
    {
        $hit->resolved_at = now();
        $hit->save();

        return redirect()->route('admin.errors.index')->with('confirm', Confirm::make(
            __('loop.error_marked_fixed_title'),
            __('loop.error_marked_fixed_body'),
            __('loop.done'),
            route('admin.errors.index'),
            false,
        ));
    }

    public function preview(): View
    {
        $kind = request('kind', '500');

        $exception = $kind === '404'
            ? new NotFoundHttpException('Preview: this page is missing.')
            : new RuntimeException('Preview: a page failed to load.');

        $status = $kind === '404' ? 404 : 500;

        return view('errors.branded', [
            'status' => $status,
            'title' => $status === 404 ? __('loop.error_404_title') : __('loop.error_500_title'),
            'body' => $status === 404 ? __('loop.error_404_body') : __('loop.error_500_body'),
            'exception' => $exception,
            'hit' => null,
            'debug' => true,
            'requestUrl' => url()->current().'?kind='.$kind,
        ]);
    }
}
