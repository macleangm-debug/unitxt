<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportPeriod
{
    public function __construct(
        public readonly string $preset,
        public readonly ?Carbon $from,
        public readonly ?Carbon $to,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $preset = (string) $request->query('range', 'month');
        if (! in_array($preset, ['7d', '14d', 'month', 'all', 'custom'], true)) {
            $preset = 'month';
        }

        $today = now()->endOfDay();
        $from = null;
        $to = null;

        if ($preset === '7d') {
            $from = now()->subDays(6)->startOfDay();
            $to = $today;
        } elseif ($preset === '14d') {
            $from = now()->subDays(13)->startOfDay();
            $to = $today;
        } elseif ($preset === 'month') {
            $from = now()->startOfMonth();
            $to = $today;
        } elseif ($preset === 'custom') {
            $from = self::parseDay($request->query('from'), now()->startOfMonth())?->startOfDay();
            $to = self::parseDay($request->query('to'), $today)?->endOfDay();
            if ($from && $to && $from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
            if ($from && $to && $to->gt($from->copy()->addDays(90)->endOfDay())) {
                $to = $from->copy()->addDays(90)->endOfDay();
            }
        }

        return new self($preset, $from, $to);
    }

    public function apply($query, string $column = 'created_at')
    {
        if ($this->from) {
            $query->where($column, '>=', $this->from);
        }
        if ($this->to) {
            $query->where($column, '<=', $this->to);
        }

        return $query;
    }

    public function dayCount(): int
    {
        if (! $this->from || ! $this->to) {
            return 30;
        }

        return max(1, min(90, (int) $this->from->diffInDays($this->to) + 1));
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public function query(array $extra = []): array
    {
        $params = ['range' => $this->preset];
        if ($this->preset === 'custom') {
            $params['from'] = $this->from?->toDateString();
            $params['to'] = $this->to?->toDateString();
        }

        return array_filter(array_merge($params, $extra), fn ($value) => $value !== null && $value !== '');
    }

    public function label(): string
    {
        return match ($this->preset) {
            '7d' => __('loop.report_range_7d'),
            '14d' => __('loop.report_range_14d'),
            'all' => __('loop.report_range_all'),
            'custom' => __('loop.report_range_custom'),
            default => __('loop.report_range_month'),
        };
    }

    private static function parseDay(mixed $value, Carbon $fallback): Carbon
    {
        $raw = is_string($value) ? trim($value) : '';
        if ($raw === '') {
            return $fallback;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
