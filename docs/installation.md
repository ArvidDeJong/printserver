---
title: "Installation"
nav_order: 2
description: "Install darvis/printserver in a Laravel application, generate a token, set up the box on location and check that a test page comes out."
---

# Installation

## 1. The application

```bash
composer require darvis/printserver
php artisan migrate
```

The migration creates the `print_jobs` table. There is nothing to publish; publish the config only
when you want to read the comments next to the settings:

```bash
php artisan vendor:publish --tag=printserver-config
```

## 2. A token for the box

```bash
php artisan print:token amsterdam
```

The command creates a token of 64 characters, writes it into `PRINTSERVER_TOKENS` in the `.env` and
shows the token to put on the box. A box that already had a token gets a new one, and the old one
stops working. When the config is cached, the command runs `config:clear` itself.

```dotenv
PRINTSERVER_ADMINISTRATION=amsterdam
PRINTSERVER_TOKENS="amsterdam:amsterdam:3f9a..."
```

With more than one location, run the command once per box; every box gets its own entry in the
same variable. See [Security](security.md#one-token-per-box) for why the administration in the
middle matters.

```bash
php artisan print:token --show            # which boxes have a token, without the tokens
php artisan print:token amsterdam --print  # only show the entry, leave the .env alone
```

## 3. Clean up old jobs

Without this the disk keeps every document that was ever printed. In `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('print:cleanup')->daily();
```

## 4. The box on location

The box is a mini pc or a Raspberry Pi with Linux, CUPS and the agent. [The box on
location](printbox.md) describes the whole setup: the queues in CUPS, the configuration file and
the service. In short, the box needs:

- the url of your application and the prefix of the endpoints,
- its token,
- a queue in CUPS per printer, named exactly like the key you use when you queue a job.

## Check that it works

Queue a test page and watch it come out:

```bash
php artisan print:test PRINTER01
```

The box picks it up within a few seconds. When nothing comes out, check in this order:

1. `php artisan tinker` → `Darvis\Printserver\Models\PrintJob::latest()->first()` shows the status.
   `pending` means the box never came by, `claimed` means it came by but did not report back,
   `failed` means CUPS refused the job and the `error` column says why.
2. On the box, `journalctl -u printbox -n 50` shows what the agent did.
3. `curl -s -X POST -H "Authorization: Bearer <token>" https://example.com/print/claim` from the
   box itself proves whether the box can reach the application at all.

[Troubleshooting](troubleshooting.md) covers the rest.
