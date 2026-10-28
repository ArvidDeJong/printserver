<?php

use Darvis\Printserver\PrintserverServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

test('the config is publishable under its own tag', function () {
    $paths = ServiceProvider::pathsToPublish(PrintserverServiceProvider::class, 'printserver-config');

    expect($paths)->toHaveCount(1)
        ->and(array_key_first($paths))->toEndWith('config/printserver.php')
        ->and(reset($paths))->toEndWith('config/printserver.php');
});

test('the migration of the package is loaded, so a host app only runs migrate', function () {
    expect(Schema::hasTable('print_jobs'))->toBeTrue()
        ->and(Schema::hasColumns('print_jobs', [
            'administration', 'claimed_at', 'claimed_by', 'error', 'format',
            'path', 'printed_at', 'printer', 'status', 'title', 'uuid',
        ]))->toBeTrue();
});

test('the routes can be switched off, so a host app can wire them up itself', function () {
    expect(Route::has('printserver.claim'))->toBeTrue();

    $this->rebuildWith(['printserver.routes' => false]);

    expect(Route::has('printserver.claim'))->toBeFalse();
});
