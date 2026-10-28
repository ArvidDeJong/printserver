<?php

namespace Darvis\Printserver\Services;

use Darvis\Printserver\Models\PrintJob;
use Darvis\Printserver\Support\PrintserverConfig;
use Darvis\Printserver\Support\TestPage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Queues print jobs for the boxes on location and cleans them up again. This is
 * the only place where a job is created.
 */
class PrintQueue
{
    /**
     * Queues one job for a printer.
     *
     * @param  string  $printer  The key of the printer, equal to the key in the configuration of the box
     * @param  string  $content  Raw content: a PDF, or the bytes for a label printer
     * @param  string|null  $title  Description shown in the log of the box
     * @param  string  $format  pdf or zpl
     */
    public function queue(string $printer, string $content, ?string $title = null, string $format = 'pdf'): PrintJob
    {
        $uuid = (string) Str::uuid();
        $path = PrintserverConfig::path().'/'.$uuid.'.'.$format;

        Storage::disk(PrintserverConfig::disk())->put($path, $content);

        return PrintJob::create([
            'administration' => $this->administration(),
            'format' => $format,
            'path' => $path,
            'printer' => $printer,
            'status' => PrintJob::STATUS_PENDING,
            'title' => $title,
            'uuid' => $uuid,
        ]);
    }

    /**
     * Queues a test page in the paper or label size of the printer.
     *
     * Without a text the page names the printer, its size, the administration and the
     * time, so a sheet that comes out tells you where it was meant to go.
     */
    public function queueTestPage(string $printer, ?string $text = null): PrintJob
    {
        $size = PrintserverConfig::printerSize($printer);
        $name = PrintserverConfig::printers()[$printer] ?? $printer;

        $text ??= implode("\n", [
            'Test page',
            $name === $printer ? $printer : $printer.': '.$name,
            'Size: '.($size === 'a4' ? 'A4' : $size.' inch'),
            'Administration: '.(PrintserverConfig::administration() ?? 'none'),
            now()->format('d-m-Y H:i:s'),
        ]);

        return $this->queue($printer, TestPage::pdf($text, $size), 'Test page '.$printer);
    }

    /**
     * Removes jobs and their files older than the given number of days.
     *
     * @return int The number of jobs removed
     */
    public function cleanup(?int $days = null): int
    {
        $days = $days === null ? PrintserverConfig::keepDays() : max(1, $days);
        $jobs = PrintJob::where('created_at', '<', now()->subDays($days))->get();

        foreach ($jobs as $job) {
            $job->forgetFile();
            $job->delete();
        }

        return $jobs->count();
    }

    /**
     * The administration of this installation, or null when there is only one.
     */
    public function administration(): ?string
    {
        return PrintserverConfig::administration();
    }
}
