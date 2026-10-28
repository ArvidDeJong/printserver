<?php

declare(strict_types=1);

namespace Darvis\Printserver\Support;

/**
 * The one place that reads the package config. Callers ask this class, so a default is
 * written once and a caller cannot quietly disagree with the config file about what it is.
 */
final class PrintserverConfig
{
    /**
     * The administration this installation belongs to, or null when there is only one.
     */
    public static function administration(): ?string
    {
        $administration = config('printserver.administration');

        return $administration === null || $administration === '' ? null : (string) $administration;
    }

    /**
     * The addresses that may reach the endpoints. Empty means every address is allowed.
     *
     * @return list<string>
     */
    public static function allowedIps(): array
    {
        /** @var array<array-key, mixed> $allowed */
        $allowed = (array) config('printserver.allowed_ips', []);

        return array_values(array_filter(array_map(
            static fn (mixed $address): string => trim((string) $address),
            $allowed
        )));
    }

    /**
     * How many jobs one box may claim in a single round. Never below 1.
     */
    public static function claimLimit(): int
    {
        return max(1, (int) config('printserver.claim_limit', 5));
    }

    /**
     * The filesystem disk that holds the content of a job.
     */
    public static function disk(): string
    {
        return (string) config('printserver.disk', 'local');
    }

    /**
     * Days after which print:cleanup removes jobs and their files. Never below 1.
     */
    public static function keepDays(): int
    {
        return max(1, (int) config('printserver.keep_days', 7));
    }

    /**
     * Extra middleware for the endpoints, on top of the token check.
     *
     * @return list<class-string|string>
     */
    public static function middleware(): array
    {
        /** @var array<array-key, class-string|string> $middleware */
        $middleware = (array) config('printserver.middleware', []);

        return array_values($middleware);
    }

    /**
     * Where the files of a job are stored on the disk, without a trailing slash.
     */
    public static function path(): string
    {
        return trim((string) config('printserver.path', 'print-jobs'), '/');
    }

    /**
     * The printers print:test accepts, as key => description. Empty accepts any name.
     *
     * @return array<string, string>
     */
    public static function printers(): array
    {
        /** @var array<string, string> $printers */
        $printers = (array) config('printserver.printers', []);

        return $printers;
    }

    /**
     * The url prefix of the endpoints, without slashes around it.
     */
    public static function prefix(): string
    {
        return trim((string) config('printserver.prefix', 'print'), '/');
    }

    /**
     * Whether this package registers its own routes.
     */
    public static function routesEnabled(): bool
    {
        return (bool) config('printserver.routes', true);
    }

    /**
     * The configured boxes, parsed from name:administration:token or the short name:token.
     *
     * An entry without a name counts as a token on its own, so a single box needs no name.
     *
     * @return list<array{administration: string, name: string, token: string}>
     */
    public static function boxes(): array
    {
        $boxes = [];

        foreach (array_filter(array_map('trim', explode(',', (string) config('printserver.tokens', '')))) as $entry) {
            $parts = explode(':', $entry);

            [$name, $administration, $token] = match (count($parts)) {
                3 => $parts,
                2 => [$parts[0], '', $parts[1]],
                default => ['unknown', '', $entry],
            };

            if (trim($token) === '') {
                continue;
            }

            $boxes[] = [
                'administration' => trim($administration),
                'name' => substr(trim($name), 0, 50),
                'token' => trim($token),
            ];
        }

        return $boxes;
    }
}
