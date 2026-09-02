<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ExceptionHit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'fingerprint',
        'method',
        'path',
        'url',
        'route_name',
        'status_code',
        'exception_class',
        'message',
        'file',
        'line',
        'sample_trace',
        'hits',
        'first_seen_at',
        'last_seen_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public static function record(Throwable $e, Request $request, int $status): ?self
    {
        try {
            if (! Schema::hasTable('exception_hits')) {
                return null;
            }

            $path = '/'.ltrim($request->path(), '/');
            if (preg_match('/\.(css|js|map|ico|png|jpe?g|gif|svg|woff2?|ttf)$/i', $path)) {
                return null;
            }

            $class = $e::class;
            $message = mb_substr($e->getMessage() ?: $class, 0, 2000);
            $fingerprint = sha1(strtoupper($request->method()).'|'.mb_strtolower($path).'|'.$class.'|'.mb_substr($message, 0, 180).'|'.$status);

            $hit = self::query()->where('fingerprint', $fingerprint)->first();
            if ($hit) {
                $hit->hits = (int) $hit->hits + 1;
                $hit->last_seen_at = now();
                $hit->url = mb_substr($request->fullUrl(), 0, 1024);
                $hit->resolved_at = null;
                $hit->save();

                return $hit;
            }

            return self::query()->create([
                'fingerprint' => $fingerprint,
                'method' => strtoupper($request->method()),
                'path' => mb_substr($path, 0, 512),
                'url' => mb_substr($request->fullUrl(), 0, 1024),
                'route_name' => $request->route()?->getName(),
                'status_code' => $status,
                'exception_class' => mb_substr($class, 0, 191),
                'message' => $message,
                'file' => mb_substr((string) $e->getFile(), 0, 512) ?: null,
                'line' => $e->getLine() ?: null,
                'sample_trace' => mb_substr($e->getTraceAsString(), 0, 4000),
                'hits' => 1,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    public static function statusFor(Throwable $e): int
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        return 500;
    }

    public static function openCount(): int
    {
        try {
            if (! Schema::hasTable('exception_hits')) {
                return 0;
            }

            return (int) self::query()->whereNull('resolved_at')->count();
        } catch (Throwable) {
            return 0;
        }
    }
}
