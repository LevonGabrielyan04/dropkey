<?php

namespace App\Support;

class ApplicationOrigins
{
    /**
     * Resolve the WebAuthn relying party ID for the current request.
     *
     * Browsers require the RP ID to match the page hostname. In local
     * development we allow both localhost and 127.0.0.1, so the RP ID must
     * follow whichever host the user is actually visiting.
     */
    public static function relyingPartyId(?string $requestHost = null): string
    {
        $defaultHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($defaultHost) || $defaultHost === '') {
            return 'localhost';
        }

        if ($requestHost === null || $requestHost === '') {
            return $defaultHost;
        }

        $allowedHosts = array_values(array_filter(array_map(
            static function (string $origin): ?string {
                $host = parse_url($origin, PHP_URL_HOST);

                return is_string($host) ? $host : null;
            },
            self::webAuthn(),
        )));

        if (in_array($requestHost, $allowedHosts, true)) {
            return $requestHost;
        }

        return $defaultHost;
    }

    /**
     * @return list<string>
     */
    public static function webAuthn(): array
    {
        $origins = [rtrim(config('app.url'), '/')];

        if (in_array(config('app.env'), ['local', 'testing'], true)) {
            $alternateOrigin = self::localHostAlternateOrigin(config('app.url'));

            if ($alternateOrigin !== null) {
                $origins[] = $alternateOrigin;
            }
        }

        return array_values(array_unique($origins));
    }

    private static function localHostAlternateOrigin(string $url): ?string
    {
        $parsed = parse_url(rtrim($url, '/'));

        if (! isset($parsed['host'])) {
            return null;
        }

        $alternateHost = match ($parsed['host']) {
            'localhost' => '127.0.0.1',
            '127.0.0.1' => 'localhost',
            default => null,
        };

        if ($alternateHost === null) {
            return null;
        }

        $scheme = $parsed['scheme'] ?? 'http';
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';

        return "{$scheme}://{$alternateHost}{$port}";
    }
}
