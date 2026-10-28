---
name: printserver-development
description: Work with darvis/printserver. Use it to queue print jobs for a box on location, add a location or a printer, test printing, and find out why a job did not come out.
---

# darvis/printserver development

## When to use this skill

Use this skill when an application that has `darvis/printserver` installed needs to print, when a new location or printer is added, when printing code needs tests, or when a job did not come out.

## Queueing

```php
use Darvis\Printserver\Services\PrintQueue;

app(PrintQueue::class)->queue('PRINTER01', $pdf, 'Invoice 2026-0042');
app(PrintQueue::class)->queue('ZEBRA01', $zpl, 'Label 12345', 'zpl');
app(PrintQueue::class)->queueTestPage('PRINTER01');
```

The content goes to the disk (`printserver.disk`, under `printserver.path`); the row keeps the path. The job gets the status `pending` and the administration of this installation.

## The chain

| Step | Endpoint | Status after |
| --- | --- | --- |
| Box claims the waiting jobs, locked in one transaction | `POST <prefix>/claim` | `claimed` |
| Box fetches the content | `GET <prefix>/file/{uuid}` | `claimed` |
| CUPS accepted it, file dropped | `POST <prefix>/done/{uuid}` | `printed` |
| CUPS refused it, reason in `error` | `POST <prefix>/failed/{uuid}` | `failed` |

The status of a stuck job says where to look: `pending` means the box never came by, `claimed` means it stopped halfway, `failed` holds the CUPS error. A failed job is never retried automatically; queue a new one after fixing the cause.

## Adding a location

1. `php artisan print:token <name>` on the installation of that location. It writes the entry into `PRINTSERVER_TOKENS` in the `.env`.
2. Set `PRINTSERVER_ADMINISTRATION` when one codebase serves several locations.
3. Put the token, the url and `base_path` (the `printserver.prefix` with a leading slash) in the configuration of the box.
4. `php artisan config:clear`, then `php artisan print:test <printer>`.

## Adding a printer

Create a CUPS queue on the box named exactly like the key the application uses: `lpadmin -m everywhere` for an IPP printer, a raw `socket://<ip>:9100` queue for a label printer. Add the key to `printserver.printers` when `print:test` should only accept known names.

## Testing printing code

```php
use Darvis\Printserver\Models\PrintJob;
use Illuminate\Support\Facades\Storage;

Storage::fake('local');

$this->post('/orders/12345/print')->assertOk();

expect(PrintJob::where('printer', 'PRINTER01')->where('status', PrintJob::STATUS_PENDING)->count())->toBe(1);
```

To test against the endpoints, set `printserver.tokens` to `test:secret` and send `Authorization: Bearer secret`.
