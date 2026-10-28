<?php

use Darvis\Printserver\Models\PrintJob;
use Darvis\Printserver\Services\PrintQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');
    config(['printserver.administration' => 'amsterdam']);
});

/**
 * @return array<string, string>
 */
function box(string $token = 'token-amsterdam'): array
{
    return ['Authorization' => 'Bearer '.$token];
}

test('a box claims the waiting jobs and they are marked claimed', function () {
    $job = app(PrintQueue::class)->queue('ZEBRA01', 'labeldata', 'Label 1', 'zpl');

    $this->postJson('print/claim', [], box())
        ->assertOk()
        ->assertJsonPath('jobs.0.uuid', $job->uuid)
        ->assertJsonPath('jobs.0.printer', 'ZEBRA01')
        ->assertJsonPath('jobs.0.format', 'zpl');

    expect($job->refresh()->status)->toBe(PrintJob::STATUS_CLAIMED)
        ->and($job->claimed_by)->toBe('amsterdam')
        ->and($job->claimed_at)->not->toBeNull();
});

test('a claimed job is never handed out twice, because a sheet may not come out twice', function () {
    app(PrintQueue::class)->queue('PRINTER01', 'pdfdata');

    $this->postJson('print/claim', [], box())->assertJsonCount(1, 'jobs');
    $this->postJson('print/claim', [], box())->assertJsonCount(0, 'jobs');
});

test('a box never gets the work of another administration', function () {
    $mine = app(PrintQueue::class)->queue('PRINTER01', 'pdfdata');

    PrintJob::where('id', $mine->id)->update(['administration' => 'rotterdam']);

    $this->postJson('print/claim', [], box())->assertJsonCount(0, 'jobs');
    expect($mine->refresh()->status)->toBe(PrintJob::STATUS_PENDING);
});

test('a token of another administration is refused instead of served', function () {
    $this->postJson('print/claim', [], box('token-rotterdam'))
        ->assertForbidden()
        ->assertJsonPath('message', 'This token belongs to administration rotterdam, this installation is amsterdam.');
});

test('a claim takes no more jobs than the limit allows', function () {
    config(['printserver.claim_limit' => 2]);

    foreach (range(1, 5) as $number) {
        app(PrintQueue::class)->queue('PRINTER01', 'pdfdata '.$number);
    }

    $this->postJson('print/claim', [], box())->assertJsonCount(2, 'jobs');
});

test('the box fetches the content of a job', function () {
    $job = app(PrintQueue::class)->queue('ZEBRA01', 'labeldata', null, 'zpl');

    $response = $this->get('print/file/'.$job->uuid, box());

    $response->assertOk()->assertHeader('Content-Type', 'application/octet-stream');
    expect($response->getContent())->toBe('labeldata');
});

test('a job the box reports as done is marked printed and its file is dropped', function () {
    $job = app(PrintQueue::class)->queue('PRINTER01', 'pdfdata');

    $this->postJson('print/done/'.$job->uuid, [], box())->assertJsonPath('status', 'printed');

    expect($job->refresh()->status)->toBe(PrintJob::STATUS_PRINTED)
        ->and($job->printed_at)->not->toBeNull()
        ->and(Storage::disk('local')->exists($job->path))->toBeFalse();

    $this->get('print/file/'.$job->uuid, box())->assertStatus(410);
});

test('a failed job stays failed and is not queued again', function () {
    $job = app(PrintQueue::class)->queue('PRINTER01', 'pdfdata');

    $this->postJson('print/failed/'.$job->uuid, ['error' => 'printer offline'], box())
        ->assertJsonPath('status', 'failed');

    expect($job->refresh()->status)->toBe(PrintJob::STATUS_FAILED)
        ->and($job->error)->toBe('printer offline');

    $this->postJson('print/claim', [], box())->assertJsonCount(0, 'jobs');
});

test('without a valid token nothing is handed out', function () {
    app(PrintQueue::class)->queue('PRINTER01', 'pdfdata');

    $this->postJson('print/claim')->assertUnauthorized();
    $this->postJson('print/claim', [], box('wrong'))->assertUnauthorized();
});

test('an address outside the allow list is refused before the token is read', function () {
    config(['printserver.allowed_ips' => ['203.0.113.10']]);

    $this->postJson('print/claim', [], box())
        ->assertForbidden()
        ->assertJsonPath('message', 'This address is not allowed here.');

    config(['printserver.allowed_ips' => ['127.0.0.1']]);

    $this->postJson('print/claim', [], box())->assertOk();
});

test('the prefix of the endpoints follows the configuration', function () {
    // The routes read the prefix when they are registered, so the application is built with it.
    $this->rebuildWith(['printserver.administration' => 'amsterdam', 'printserver.prefix' => 'shop/print']);

    $this->postJson('shop/print/claim', [], box())->assertOk();
    $this->postJson('print/claim', [], box())->assertNotFound();
});

test('an unknown job gives a 404 and not an empty sheet', function () {
    $this->get('print/file/'.Str::uuid(), box())->assertNotFound();
    $this->postJson('print/done/'.Str::uuid(), [], box())->assertNotFound();
});

test('one installation serves several locations, and every box only gets its own work', function () {
    config([
        'printserver.printers' => [
            'home-hp' => ['administration' => 'home', 'name' => 'HP at home', 'printer' => 'PRINTER01'],
            'PRINTER01' => 'Office',
        ],
        'printserver.tokens' => 'amsterdam:amsterdam:token-amsterdam,pi:home:token-home',
    ]);

    $office = app(PrintQueue::class)->queue('PRINTER01', 'office');
    $home = app(PrintQueue::class)->queue('home-hp', 'home');

    expect($home->administration)->toBe('home')
        ->and($home->printer)->toBe('PRINTER01')
        ->and($office->administration)->toBe('amsterdam');

    $this->postJson('print/claim', [], box('token-home'))
        ->assertJsonCount(1, 'jobs')
        ->assertJsonPath('jobs.0.uuid', $home->uuid)
        ->assertJsonPath('jobs.0.printer', 'PRINTER01');

    $this->postJson('print/claim', [], box())
        ->assertJsonCount(1, 'jobs')
        ->assertJsonPath('jobs.0.uuid', $office->uuid);
});

test('a box cannot fetch or report a job of another location, even with its uuid', function () {
    config([
        'printserver.printers' => ['home-hp' => ['administration' => 'home']],
        'printserver.tokens' => 'amsterdam:amsterdam:token-amsterdam,pi:home:token-home',
    ]);

    $home = app(PrintQueue::class)->queue('home-hp', 'home');

    $this->get('print/file/'.$home->uuid, box())->assertNotFound();
    $this->postJson('print/done/'.$home->uuid, [], box())->assertNotFound();
    $this->postJson('print/failed/'.$home->uuid, [], box())->assertNotFound();

    expect($home->refresh()->status)->toBe('pending');
});

test('a token of an administration that no printer and no installation has is refused', function () {
    config([
        'printserver.printers' => ['home-hp' => ['administration' => 'home']],
        'printserver.tokens' => 'other:utrecht:token-utrecht',
    ]);

    $this->postJson('print/claim', [], box('token-utrecht'))
        ->assertForbidden()
        ->assertJsonPath('message', 'This token belongs to administration utrecht, this installation is amsterdam, home.');
});
