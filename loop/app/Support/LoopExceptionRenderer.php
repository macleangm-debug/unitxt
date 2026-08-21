<?php

namespace App\Support;

use App\Models\ExceptionHit;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LoopExceptionRenderer
{
    public static function response(Throwable $e, Request $request): ?Response
    {
        if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
            return null;
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }

        $status = ExceptionHit::statusFor($e);
        if ($status < 400) {
            $status = 500;
        }

        $hit = ExceptionHit::record($e, $request, $status);

        $title = match (true) {
            $status === 404 => __('loop.error_404_title'),
            $status === 403 => __('loop.error_403_title'),
            $e instanceof TokenMismatchException || $status === 419 => __('loop.error_419_title'),
            default => __('loop.error_500_title'),
        };

        $body = match (true) {
            $status === 404 => __('loop.error_404_body'),
            $status === 403 => __('loop.error_403_body'),
            $e instanceof TokenMismatchException || $status === 419 => __('loop.error_419_body'),
            default => __('loop.error_500_body'),
        };

        $debug = (bool) config('app.debug');

        try {
            return response()->view('errors.branded', [
                'status' => $status,
                'title' => $title,
                'body' => $body,
                'exception' => $e,
                'hit' => $hit,
                'debug' => $debug,
                'requestUrl' => $request->fullUrl(),
            ], $status);
        } catch (Throwable) {
            return response(
                'Loop could not load this page. ('.$status.')',
                $status,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }
    }
}
