<?php

namespace Darvis\Printserver\Console\Commands;

use Darvis\Printserver\Services\PrintQueue;
use Darvis\Printserver\Support\PrintserverConfig;
use Illuminate\Console\Command;

/**
 * Queues a test page, so you can see whether a box picks up its work and whether the
 * printer answers, without the application having to send anything.
 */
class TestPageCommand extends Command
{
    protected $signature = 'print:test
        {printer : The key of the printer, equal to the name of the queue on the box}
        {--text= : The line printed on the page}';

    protected $description = 'Queue a test page for a printer';

    public function handle(PrintQueue $queue): int
    {
        $printer = $this->argument('printer');
        $text = $this->option('text');
        $printers = PrintserverConfig::printers();

        if (! is_string($printer) || $printer === '') {
            $this->components->error('Give the key of a printer, for example print:test PRINTER01.');

            return self::FAILURE;
        }

        if ($printers !== [] && ! array_key_exists($printer, $printers)) {
            $this->components->error('Unknown printer '.$printer.'. Known printers: '.implode(', ', array_keys($printers)).'.');

            return self::FAILURE;
        }

        $job = $queue->queueTestPage($printer, is_string($text) ? $text : null);

        $this->newLine();
        $this->components->twoColumnDetail('Printer', $job->printer);
        $this->components->twoColumnDetail('Administration', $job->administration ?? 'none');
        $this->components->twoColumnDetail('Uuid', $job->uuid);
        $this->components->twoColumnDetail('Status', $job->status);
        $this->newLine();
        $this->components->info('Queued. The box picks it up within a few seconds.');

        return self::SUCCESS;
    }
}
