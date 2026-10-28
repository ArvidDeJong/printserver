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
| `printers` | | empty | The printers of this installation, each with a name and optionally the size of its paper or label. `print:test` only accepts these; empty accepts any name. |
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

## Printers and their sizes

```php
'printers' => [
    'PRINTER01' => 'Office printer',
    'ZEBRA01' => ['name' => 'Label printer, stock', 'size' => '4x2'],
    'ZEBRA02' => ['name' => 'Label printer, shipping', 'size' => '4x6'],
],
```

The key is the one your application queues to, and the name of the printer on the box. A plain
string is the name. An array adds the `size`: `a4` (the default), `4x2`, `4x4` or `4x6`, the last
three in inches as label rolls are sold. The size decides the size of a test page, so a label
printer gets a test label instead of an A4 it cannot handle.

## Several locations in one installation

Usually every location has an installation of its own, with its own `PRINTSERVER_ADMINISTRATION`.
When one installation serves several locations, a test setup for instance, give each printer the
administration of its location:

```php
'printers' => [
    'home-hp' => ['administration' => 'home', 'name' => 'HP at home', 'printer' => 'HPHOME'],
    'office-konika' => ['administration' => 'amsterdam', 'name' => 'Konica', 'printer' => 'konika'],
    'shop-konika' => ['administration' => 'rotterdam', 'name' => 'Konica', 'printer' => 'konika'],
],
```

- A job gets the administration of its printer, and only a box of that administration receives
  it. A box with a token for `home` never sees the work of `amsterdam`, not even by its uuid.
- A box gets in when its administration is the one of the installation or one that a printer
  names. Any other is refused with a `403`.
- `printer` is the name the box knows the printer by. Leave it out when that is the key. It lets
  two locations both have a `konika` in one list.

Make a token per box with its administration: `php artisan print:token pi --administration=home`.

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
