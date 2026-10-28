---
title: "Configuration"
nav_order: 5
description: "Every setting of darvis/printserver with its environment variable: administration, allowed addresses, claim limit, disk, keep days, prefix, printers and tokens."
---

# Configuration

Publish the config file to read the comments next to the settings:

```bash
php artisan vendor:publish --tag=printserver-config
```

Everything has a default, so an installation with one location only needs `PRINTSERVER_TOKENS`.

## The settings

| Key | Variable | Default | What it does |
| --- | --- | --- | --- |
| `administration` | `PRINTSERVER_ADMINISTRATION` | `ADMINISTRATION` | The administration this installation belongs to. A token may name one; when it does and it differs, the box is refused. Leave it empty when there is only one. |
| `allowed_ips` | `PRINTSERVER_ALLOWED_IPS` | empty | Comma separated addresses that may reach the endpoints. Empty allows every address and the token does all the work. |
| `claim_limit` | `PRINTSERVER_CLAIM_LIMIT` | `5` | How many jobs a box may claim in one round. |
| `disk` | `PRINTSERVER_DISK` | `local` | The filesystem disk that holds the content of a job. |
| `keep_days` | `PRINTSERVER_KEEP_DAYS` | `7` | Days after which `print:cleanup` removes jobs and their files. |
| `middleware` | | empty | Extra middleware for the endpoints, on top of the token check. |
| `path` | `PRINTSERVER_PATH` | `print-jobs` | Where the files live on that disk. |
| `prefix` | `PRINTSERVER_PREFIX` | `print` | The url prefix of the endpoints. The box calls `<prefix>/claim`. |
| `printers` | | empty | The printers `print:test` accepts, as key and description. Empty accepts any name. |
| `routes` | | `true` | Whether this package registers its own routes. |
| `tokens` | `PRINTSERVER_TOKENS` | empty | One entry per box, as `name:administration:token`, separated by commas. |

## A typical .env

```dotenv
PRINTSERVER_ADMINISTRATION=amsterdam
PRINTSERVER_ALLOWED_IPS=203.0.113.10
PRINTSERVER_TOKENS="amsterdam:amsterdam:nSbQ...,rotterdam:rotterdam:7kPl..."
```

A token names the box, and that name ends up in the `claimed_by` column, so a job tells you which
box printed it.

## Your own routes

Set `routes` to `false` to wire the endpoints up yourself, for instance inside an existing group:

```php
Route::prefix('shop/print')->middleware([VerifyPrintboxToken::class])->group(function () {
    Route::post('/claim', [PrintJobController::class, 'claim']);
    Route::get('/file/{uuid}', [PrintJobController::class, 'file']);
    Route::post('/done/{uuid}', [PrintJobController::class, 'done']);
    Route::post('/failed/{uuid}', [PrintJobController::class, 'failed']);
});
```

Changing `prefix` is enough for most applications; a box that already runs against another prefix
keeps working as long as its `base_path` matches.

## Reading the config in your own code

`Darvis\Printserver\Support\PrintserverConfig` has a named accessor per setting. The package reads
its config nowhere else, and neither should you: `PrintserverConfig::prefix()` is right,
`config('printserver.prefix')` repeats the default.
