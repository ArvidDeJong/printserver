<?php

use Darvis\Printserver\Models\PrintJob;
use Darvis\Printserver\Services\PrintQueue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('a queued job keeps its content on the disk and only the path in the database', function () {
    config(['printserver.administration' => 'amsterdam']);

    $job = app(PrintQueue::class)->queue('ZEBRA01', 'labeldata', 'Label 1', 'zpl');

    expect($job->administration)->toBe('amsterdam')
        ->and($job->status)->toBe(PrintJob::STATUS_PENDING)
        ->and($job->path)->toBe('print-jobs/'.$job->uuid.'.zpl')
        ->and($job->contents())->toBe('labeldata');

    Storage::disk('local')->assertExists($job->path);
});

test('a job of an installation without an administration has none', function () {
    expect(app(PrintQueue::class)->queue('PRINTER01', 'pdfdata')->administration)->toBeNull();
});

test('a test page is a pdf with the name of the printer on it', function () {
    $job = app(PrintQueue::class)->queueTestPage('PRINTER01');

    expect($job->format)->toBe('pdf')
        ->and($job->title)->toBe('Test page PRINTER01')
        ->and((string) $job->contents())->toStartWith('%PDF');
});

test('cleanup removes old jobs with their files and leaves the recent ones alone', function () {
    $old = app(PrintQueue::class)->queue('PRINTER01', 'old');
    $recent = app(PrintQueue::class)->queue('PRINTER01', 'recent');

    PrintJob::where('id', $old->id)->update(['created_at' => now()->subDays(8)]);

    expect(app(PrintQueue::class)->cleanup())->toBe(1);

    Storage::disk('local')->assertMissing($old->path);
    Storage::disk('local')->assertExists($recent->path);
    expect(PrintJob::count())->toBe(1);
});

test('the path and the disk follow the configuration', function () {
    Storage::fake('prints');
    config(['printserver.disk' => 'prints', 'printserver.path' => 'shop/jobs']);

    $job = app(PrintQueue::class)->queue('PRINTER01', 'pdfdata');

    expect($job->path)->toBe('shop/jobs/'.$job->uuid.'.pdf');
    Storage::disk('prints')->assertExists($job->path);
});
