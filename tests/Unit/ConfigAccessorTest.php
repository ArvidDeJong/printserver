<?php

use Darvis\Printserver\Support\PrintserverConfig;

test('the package reads its config in one place', function () {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/src'));

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php' || $file->getBasename() === 'PrintserverConfig.php') {
            continue;
        }

        expect(file_get_contents($file->getPathname()))->not->toContain("config('printserver.", $file->getBasename());
    }
});

test('the defaults are the ones documented in the config file', function () {
    expect(PrintserverConfig::claimLimit())->toBe(5)
        ->and(PrintserverConfig::disk())->toBe('local')
        ->and(PrintserverConfig::keepDays())->toBe(7)
        ->and(PrintserverConfig::middleware())->toBe([])
        ->and(PrintserverConfig::path())->toBe('print-jobs')
        ->and(PrintserverConfig::printers())->toBe([])
        ->and(PrintserverConfig::prefix())->toBe('print')
        ->and(PrintserverConfig::routesEnabled())->toBeTrue();
});

test('a limit below one is raised to one, because a box without work loops forever', function () {
    config(['printserver.claim_limit' => 0, 'printserver.keep_days' => 0]);

    expect(PrintserverConfig::claimLimit())->toBe(1)
        ->and(PrintserverConfig::keepDays())->toBe(1);
});

test('an empty administration counts as no administration', function () {
    config(['printserver.administration' => '']);

    expect(PrintserverConfig::administration())->toBeNull();

    config(['printserver.administration' => 'amsterdam']);

    expect(PrintserverConfig::administration())->toBe('amsterdam');
});

test('tokens are parsed in both the long and the short form', function () {
    config(['printserver.tokens' => ' amsterdam:amsterdam:aaa , solo:bbb , ccc , :: ']);

    expect(PrintserverConfig::boxes())->toBe([
        ['administration' => 'amsterdam', 'name' => 'amsterdam', 'token' => 'aaa'],
        ['administration' => '', 'name' => 'solo', 'token' => 'bbb'],
        ['administration' => '', 'name' => 'unknown', 'token' => 'ccc'],
    ]);
});

test('the prefix and the path lose their slashes, so a url never has a double one', function () {
    config(['printserver.prefix' => '/shop/print/', 'printserver.path' => '/shop/print-jobs/']);

    expect(PrintserverConfig::prefix())->toBe('shop/print')
        ->and(PrintserverConfig::path())->toBe('shop/print-jobs');
});
