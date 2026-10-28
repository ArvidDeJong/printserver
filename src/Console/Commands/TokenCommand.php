<?php

namespace Darvis\Printserver\Console\Commands;

use Darvis\Printserver\Support\PrintserverConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Creates a token for one print box and writes it into PRINTSERVER_TOKENS in the .env file.
 * Every box gets its own token, so one can be withdrawn without touching the others.
 */
class TokenCommand extends Command
{
    protected $signature = 'print:token
        {name? : The name of the box, which ends up in the claimed_by column}
        {--administration= : Lock the token to this administration, defaults to the one of this installation}
        {--no-administration : Do not lock the token to an administration}
        {--show : Only list the boxes that have a token, without the tokens}
        {--print : Only print the entry and leave the .env alone}';

    protected $description = 'Create a token for a print box';

    public function handle(): int
    {
        if ($this->option('show')) {
            return $this->showBoxes();
        }

        $name = $this->argument('name');
        $name = Str::slug(is_string($name) ? $name : '');

        if ($name === '') {
            $this->components->error('Give the box a name, for example: php artisan print:token amsterdam');

            return self::FAILURE;
        }

        $token = bin2hex(random_bytes(32));
        $administration = $this->administration();
        $entry = $administration === null ? $name.':'.$token : $name.':'.$administration.':'.$token;

        if ($this->option('print')) {
            $this->line($entry);

            return self::SUCCESS;
        }

        if (! $this->writeToEnv($name, $entry)) {
            return self::FAILURE;
        }

        $this->components->info('Token for '.$name.' written to PRINTSERVER_TOKENS in the .env.');
        $this->line('Put this in the configuration of the box:');
        $this->newLine();
        $this->line('  "token": "'.$token.'"');
        $this->newLine();

        if ($this->laravel->configurationIsCached()) {
            $this->call('config:clear');
        }

        return self::SUCCESS;
    }

    /**
     * The administration the token is locked to, or null for a token without one.
     */
    private function administration(): ?string
    {
        $chosen = $this->option('administration');

        return match (true) {
            (bool) $this->option('no-administration') => null,
            is_string($chosen) && $chosen !== '' => $chosen,
            default => PrintserverConfig::administration(),
        };
    }

    /**
     * Lists the boxes that have a token, without showing the tokens themselves.
     */
    private function showBoxes(): int
    {
        $boxes = PrintserverConfig::boxes();

        if ($boxes === []) {
            $this->components->warn('There is no token in PRINTSERVER_TOKENS yet.');

            return self::SUCCESS;
        }

        foreach ($boxes as $box) {
            $this->components->twoColumnDetail(
                $box['name'],
                ($box['administration'] === '' ? 'no administration' : $box['administration']).', '.strlen($box['token']).' characters'
            );
        }

        return self::SUCCESS;
    }

    /**
     * Puts the entry in PRINTSERVER_TOKENS, replacing an older token of the same box so a
     * name stays unique and the old token stops working.
     */
    private function writeToEnv(string $name, string $entry): bool
    {
        $path = $this->laravel->environmentFilePath();

        if (! is_file($path) || ! is_writable($path)) {
            $this->components->error('Cannot write the .env file: '.$path);
            $this->line('Add this entry to PRINTSERVER_TOKENS by hand:');
            $this->line('  '.$entry);

            return false;
        }

        $contents = (string) file_get_contents($path);
        $current = [];

        if (preg_match('/^PRINTSERVER_TOKENS=(.*)$/m', $contents, $matches)) {
            $current = array_filter(array_map('trim', explode(',', trim($matches[1], " \"'"))));
        }

        $current = array_values(array_filter(
            $current,
            static fn (string $existing): bool => ! str_starts_with($existing, $name.':'),
        ));
        $current[] = $entry;

        $line = 'PRINTSERVER_TOKENS="'.implode(',', $current).'"';

        $contents = preg_match('/^PRINTSERVER_TOKENS=.*$/m', $contents)
            ? (string) preg_replace('/^PRINTSERVER_TOKENS=.*$/m', $line, $contents)
            : rtrim($contents)."\n\n".$line."\n";

        file_put_contents($path, $contents);

        return true;
    }
}
