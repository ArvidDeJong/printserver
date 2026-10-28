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
     * Every administration a box may belong to: the one of this installation and the ones
     * printers name themselves. A box of any other administration is refused.
     *
     * @return list<string>
     */
    public static function administrations(): array
    {
        $administrations = [self::administration()];

        foreach (array_keys(self::printerEntries()) as $printer) {
            $administrations[] = self::printerAdministration($printer);
        }

        return array_values(array_unique(array_filter($administrations, static fn (?string $value): bool => $value !== null)));
    }

    /**
     * The administration a printer belongs to: its own when the config names one, otherwise
     * the one of this installation. A job for this printer is only handed to a box of that
     * administration.
     */
    public static function printerAdministration(string $printer): ?string
    {
        $entry = self::printerEntries()[$printer] ?? null;
        $administration = is_array($entry) ? trim((string) ($entry['administration'] ?? '')) : '';

        return $administration === '' ? self::administration() : $administration;
    }

    /**
     * The name the box knows a printer by. That is the key, unless the config gives the
     * printer another one; one installation can then hold two printers that are called the
     * same on two different boxes, such as the office printer of two branches.
     */
    public static function printerOnBox(string $printer): string
    {
        $entry = self::printerEntries()[$printer] ?? null;
        $name = is_array($entry) ? trim((string) ($entry['printer'] ?? '')) : '';

        return $name === '' ? $printer : $name;
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
     * The known printers as key => name. Empty means print:test accepts any name.
     *
     * A printer is configured as a plain name, or as an array with a name, a size, an
     * administration and the name of the printer on the box.
     *
     * @return array<string, string>
     */
    public static function printers(): array
    {
        $printers = [];

        foreach (self::printerEntries() as $key => $printer) {
            $printers[$key] = is_array($printer) ? (string) ($printer['name'] ?? $key) : (string) $printer;
        }

        return $printers;
    }

    /**
     * The paper size of a printer: a4, or a label size such as 4x2 or 4x6. A printer
     * without a size, an unknown printer and an unknown size all count as a4.
     */
    public static function printerSize(string $printer): string
    {
        $entry = self::printerEntries()[$printer] ?? null;
        $size = is_array($entry) ? strtolower((string) ($entry['size'] ?? 'a4')) : 'a4';

        return array_key_exists($size, TestPage::SIZES) ? $size : 'a4';
    }

    /**
     * @return array<string, mixed>
     */
    private static function printerEntries(): array
    {
        $entries = [];

        foreach ((array) config('printserver.printers', []) as $key => $printer) {
            $entries[(string) $key] = $printer;
        }

        return $entries;
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
