<?php

use Darvis\Printserver\Models\PrintJob;
use Darvis\Printserver\Services\PrintQueue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

/**
 * Points the application at a temporary .env, so print:token never writes into the Testbench app.
 */
function temporaryEnv(string $contents): string
{
    $directory = sys_get_temp_dir().'/printserver-'.bin2hex(random_bytes(4));
    mkdir($directory);
    file_put_contents($directory.'/.env', $contents);
    app()->useEnvironmentPath($directory);

    return $directory.'/.env';
}

test('print:token writes the token into the .env next to the existing ones', function () {
    config(['printserver.administration' => 'amsterdam']);
    $env = temporaryEnv("APP_NAME=Lab\nPRINTSERVER_TOKENS=\"rotterdam:rotterdam:token-rotterdam\"\n");

    $this->artisan('print:token', ['name' => 'Amsterdam'])->assertSuccessful();

    expect((string) file_get_contents($env))
        ->toContain('APP_NAME=Lab')
        ->toMatch('/^PRINTSERVER_TOKENS="rotterdam:rotterdam:token-rotterdam,amsterdam:amsterdam:[0-9a-f]{64}"$/m');
});

test('print:token replaces the old token of the same box, so the old one stops working', function () {
    $env = temporaryEnv("PRINTSERVER_TOKENS=\"odroid:old,pi:keep\"\n");

    $this->artisan('print:token', ['name' => 'odroid'])->assertSuccessful();

    $contents = (string) file_get_contents($env);

    expect($contents)->not->toContain('odroid:old')
        ->and($contents)->toMatch('/^PRINTSERVER_TOKENS="pi:keep,odroid:[0-9a-f]{64}"$/m');
});

test('print:token adds the variable when the .env does not have it yet', function () {
    $env = temporaryEnv("APP_NAME=Lab\n");

    $this->artisan('print:token', ['name' => 'solo'])->assertSuccessful();

    expect((string) file_get_contents($env))->toMatch('/^PRINTSERVER_TOKENS="solo:[0-9a-f]{64}"$/m');
});

test('print:token --print leaves the .env alone', function () {
    config(['printserver.administration' => 'amsterdam']);
    $env = temporaryEnv("APP_NAME=Lab\n");

    $this->artisan('print:token', ['name' => 'amsterdam', '--print' => true])
        ->expectsOutputToContain('amsterdam:amsterdam:')
        ->assertSuccessful();

    expect((string) file_get_contents($env))->toBe("APP_NAME=Lab\n");
});

test('print:token can leave the administration out', function () {
    config(['printserver.administration' => 'amsterdam']);
    temporaryEnv('');

    $this->artisan('print:token', ['name' => 'solo', '--no-administration' => true, '--print' => true])
        ->expectsOutputToContain('solo:')
        ->doesntExpectOutputToContain('amsterdam')
        ->assertSuccessful();
});

test('print:token --show lists the boxes without their tokens', function () {
    $this->artisan('print:token', ['--show' => true])
        ->expectsOutputToContain('amsterdam')
        ->doesntExpectOutputToContain('token-amsterdam')
        ->assertSuccessful();
});

test('print:token without a name fails and writes nothing', function () {
    $env = temporaryEnv("APP_NAME=Lab\n");

    $this->artisan('print:token')->assertFailed();

    expect((string) file_get_contents($env))->toBe("APP_NAME=Lab\n");
});

test('print:test queues a test page', function () {
    $this->artisan('print:test', ['printer' => 'PRINTER01'])->assertSuccessful();

    expect(PrintJob::where('printer', 'PRINTER01')->where('status', PrintJob::STATUS_PENDING)->count())->toBe(1);
});

test('print:test refuses a printer the configuration does not know', function () {
    config(['printserver.printers' => ['PRINTER01' => 'Line 2']]);

    $this->artisan('print:test', ['printer' => 'ZEBRA09'])
        ->expectsOutputToContain('Unknown printer ZEBRA09')
        ->assertFailed();

    expect(PrintJob::count())->toBe(0);
});

test('print:cleanup removes the jobs that are past their keep days', function () {
    $job = app(PrintQueue::class)->queue('PRINTER01', 'pdfdata');

    PrintJob::where('id', $job->id)->update(['created_at' => now()->subDays(30)]);

    $this->artisan('print:cleanup')
        ->expectsOutputToContain('1 print job(s) older than 7 day(s) removed.')
        ->assertSuccessful();

    expect(PrintJob::count())->toBe(0);
});
