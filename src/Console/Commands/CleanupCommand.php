<?php

namespace Darvis\Printserver\Console\Commands;

use Darvis\Printserver\Services\PrintQueue;
use Darvis\Printserver\Support\PrintserverConfig;
use Illuminate\Console\Command;

/**
 * Removes old print jobs and their files. Schedule this daily; without it the disk keeps
 * every document that has ever been printed.
 */
class CleanupCommand extends Command
{
    protected $signature = 'print:cleanup
        {--days= : Remove jobs older than this many days, defaults to printserver.keep_days}';

    protected $description = 'Remove old print jobs and their files';

    public function handle(PrintQueue $queue): int
    {
        $days = $this->option('days') === null ? PrintserverConfig::keepDays() : max(1, (int) $this->option('days'));
        $removed = $queue->cleanup($days);

        $this->components->info($removed.' print job(s) older than '.$days.' day(s) removed.');

        return self::SUCCESS;
    }
}
