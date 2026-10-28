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

test('a printer is a plain name or an array with a name and a size', function () {
    config(['printserver.printers' => [
        'PRINTER01' => 'Office',
        'ZEBRA01' => ['name' => 'Shipping', 'size' => '4X6'],
        'ZEBRA02' => ['size' => '4x2'],
        'ZEBRA03' => ['name' => 'Odd roll', 'size' => 'letter'],
    ]]);

    expect(PrintserverConfig::printers())->toBe([
        'PRINTER01' => 'Office',
        'ZEBRA01' => 'Shipping',
        'ZEBRA02' => 'ZEBRA02',
        'ZEBRA03' => 'Odd roll',
    ])
        ->and(PrintserverConfig::printerSize('PRINTER01'))->toBe('a4')
        ->and(PrintserverConfig::printerSize('ZEBRA01'))->toBe('4x6')
        ->and(PrintserverConfig::printerSize('ZEBRA02'))->toBe('4x2')
        ->and(PrintserverConfig::printerSize('ZEBRA03'))->toBe('a4')
        ->and(PrintserverConfig::printerSize('UNKNOWN'))->toBe('a4');
});

test('a printer can belong to another administration and have another name on its box', function () {
    config([
        'printserver.administration' => 'amsterdam',
        'printserver.printers' => [
            'PRINTER01' => 'Office',
            'home-hp' => ['administration' => 'home', 'printer' => 'HPHOME'],
            'rotterdam-konika' => ['administration' => 'rotterdam', 'printer' => 'konika'],
            'konika' => ['administration' => '', 'name' => 'Konica'],
        ],
    ]);

    expect(PrintserverConfig::printerAdministration('PRINTER01'))->toBe('amsterdam')
        ->and(PrintserverConfig::printerAdministration('home-hp'))->toBe('home')
        ->and(PrintserverConfig::printerAdministration('konika'))->toBe('amsterdam')
        ->and(PrintserverConfig::printerAdministration('UNKNOWN'))->toBe('amsterdam')
        ->and(PrintserverConfig::printerOnBox('home-hp'))->toBe('HPHOME')
        ->and(PrintserverConfig::printerOnBox('rotterdam-konika'))->toBe('konika')
        ->and(PrintserverConfig::printerOnBox('PRINTER01'))->toBe('PRINTER01')
        ->and(PrintserverConfig::printerOnBox('UNKNOWN'))->toBe('UNKNOWN')
        ->and(PrintserverConfig::administrations())->toBe(['amsterdam', 'home', 'rotterdam']);
});

test('without any administration there is none to allow', function () {
    config(['printserver.administration' => null, 'printserver.printers' => ['PRINTER01' => 'Office']]);

    expect(PrintserverConfig::administrations())->toBe([])
        ->and(PrintserverConfig::printerAdministration('PRINTER01'))->toBeNull();
});
