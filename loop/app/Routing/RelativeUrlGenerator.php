<?php

namespace App\Routing;

use DateInterval;
use DateTimeInterface;
use Illuminate\Routing\UrlGenerator;

/**
 * Prefer host-relative route URLs so demos/previews stay on the current origin.
 *
 * Laravel's route() helper defaults to absolute URLs. With APP_URL=127.0.0.1,
 * those links escape Cursor port-forwards and open whatever else runs locally
 * (e.g. KopaFasta on :8000). Share/signed URLs still get absolute hosts when needed.
 */
class RelativeUrlGenerator extends UrlGenerator
{
    protected bool $allowAbsolute = false;

    /**
     * {@inheritdoc}
     */
    public function route($name, $parameters = [], $absolute = false)
    {
        if (! $this->allowAbsolute) {
            $absolute = false;
        }

        return parent::route($name, $parameters, $absolute);
    }

    /**
     * {@inheritdoc}
     */
    public function action($action, $parameters = [], $absolute = false)
    {
        if (! $this->allowAbsolute) {
            $absolute = false;
        }

        return parent::action($action, $parameters, $absolute);
    }

    /**
     * Keep in-app path URLs host-relative (including redirect()->to / back()).
     *
     * {@inheritdoc}
     */
    public function to($path, $extra = [], $secure = null)
    {
        if ($this->isValidUrl($path)) {
            return $this->allowAbsolute ? $path : $this->toRelativeAppUrl($path);
        }

        if ($this->allowAbsolute) {
            return parent::to($path, $extra, $secure);
        }

        $tail = implode('/', array_map(
            'rawurlencode', (array) $this->formatParameters($extra)
        ));

        [$path, $query] = $this->extractQueryString($path);

        $relative = '/'.trim($path.'/'.$tail, '/');

        return ($relative === '/' ? '/' : rtrim($relative, '/')).$query;
    }

    /**
     * {@inheritdoc}
     */
    public function previous($fallback = false)
    {
        $url = parent::previous($fallback);

        if ($this->allowAbsolute || ! is_string($url) || $url === '') {
            return $url;
        }

        return $this->toRelativeAppUrl($url);
    }

    /**
     * Strip scheme/host so redirects stay on the browser origin (tunnel/preview).
     */
    protected function toRelativeAppUrl(string $url): string
    {
        if (! $this->isValidUrl($url)) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return '/';
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return ($path === '' ? '/' : $path).$query.$fragment;
    }

    /**
     * {@inheritdoc}
     */
    public function signedRoute($name, $parameters = [], $expiration = null, $absolute = true)
    {
        $this->allowAbsolute = true;

        try {
            return parent::signedRoute($name, $parameters, $expiration, $absolute);
        } finally {
            $this->allowAbsolute = false;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param  array|string  $name
     * @param  DateTimeInterface|DateInterval|int|null  $expiration
     */
    public function temporarySignedRoute($name, $expiration, $parameters = [], $absolute = true)
    {
        $this->allowAbsolute = true;

        try {
            return parent::temporarySignedRoute($name, $expiration, $parameters, $absolute);
        } finally {
            $this->allowAbsolute = false;
        }
    }
}
