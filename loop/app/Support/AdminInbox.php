<?php

namespace App\Support;

use App\Models\Affiliate;
use App\Models\ExceptionHit;

class AdminInbox
{
    /**
     * @return list<array{key: string, title: string, body: string, cta: string, url: string}>
     */
    public static function items(): array
    {
        $items = [];
        $errors = ExceptionHit::openCount();
        if ($errors > 0) {
            $items[] = [
                'key' => 'errors',
                'title' => trans_choice('loop.admin_errors_banner', $errors, ['count' => $errors]),
                'body' => __('loop.admin_inbox_errors_body'),
                'cta' => __('loop.admin_errors'),
                'url' => route('admin.errors.index'),
            ];
        }

        $pending = Affiliate::query()->where('status', 'pending')->count();
        if ($pending > 0) {
            $items[] = [
                'key' => 'affiliates',
                'title' => trans_choice('loop.admin_inbox_affiliates', $pending, ['count' => $pending]),
                'body' => __('loop.admin_inbox_affiliates_body'),
                'cta' => __('loop.review'),
                'url' => route('admin.affiliates.index', ['tab' => 'applications']),
            ];
        }

        return $items;
    }
}
