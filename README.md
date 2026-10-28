# Printserver for Laravel

[![Tests](https://github.com/ArvidDeJong/printserver/actions/workflows/tests.yml/badge.svg)](https://github.com/ArvidDeJong/printserver/actions/workflows/tests.yml)
[![Latest version](https://img.shields.io/packagist/v/darvis/printserver.svg)](https://packagist.org/packages/darvis/printserver)
[![License](https://img.shields.io/packagist/l/darvis/printserver.svg)](LICENSE)

Print on a printer that stands somewhere else, without opening a port on the router there.

Your application queues a print job. A small box on location (a mini pc or a Raspberry Pi with
CUPS) asks this application every few seconds whether there is work, fetches the file over HTTPS
and prints it on its own network. All traffic is outgoing from that location, so the printers are
not reachable from the internet at all.

That is the point of this package. Sending bytes straight to port 9100 of a printer behind a port
forward works until a scanner finds the port, and then the printer starts producing other people's
pages.

## Requirements

- PHP 8.2 or higher
- Laravel 11, 12 or 13
- A database and a filesystem disk
- A box on location that runs the agent, with CUPS and the printers installed

## Install

```bash
composer require darvis/printserver
php artisan migrate
php artisan print:token amsterdam
```

`print:token` writes the token into `PRINTSERVER_TOKENS` in your `.env` and shows the token for the
box. With more than one location, also set the administration of this installation:

```dotenv
PRINTSERVER_ADMINISTRATION=amsterdam
PRINTSERVER_TOKENS="amsterdam:amsterdam:3f9a..."
```

Then queue a job from your application:

```php
use Darvis\Printserver\Services\PrintQueue;

app(PrintQueue::class)->queue('ZEBRA01', $zpl, 'Label order 12345', 'zpl');
app(PrintQueue::class)->queue('PRINTER01', $pdf, 'Packing slip 12345');
```

And schedule the cleanup, or the disk keeps every document that was ever printed:

```php
Schedule::command('print:cleanup')->daily();
```

[Installation](docs/installation.md) has the full steps, including the box on location.

## The endpoints

The box uses four endpoints, all behind a bearer token:

| Method | Url | What it does |
| --- | --- | --- |
| `POST` | `print/claim` | Returns the waiting jobs and marks them claimed in one transaction |
| `GET` | `print/file/{uuid}` | The content of one job |
| `POST` | `print/done/{uuid}` | Printed; the file is dropped |
| `POST` | `print/failed/{uuid}` | Failed, with the reason |

A claimed job is never handed out again, and a failed job is not queued again on purpose: better a
sheet that does not come out than one that comes out twice.

## More than one location

A token is `name:administration:token`. The administration is the lock: a box of one branch that
points at the installation of another gets a `403` instead of its work, and `claim` only ever
returns the jobs of its own administration. Leave the administration out when there is only one.
One installation can also serve several locations by giving each printer its administration; see
[Configuration](docs/configuration.md#several-locations-in-one-installation).

## Documentation

- [Installation](docs/installation.md): the application, the box, and how to check it works
- [Queueing jobs](docs/queueing.md): the service, formats, and what the printer receives
- [The box on location](docs/printbox.md): the agent, its configuration and CUPS
- [Configuration](docs/configuration.md): every setting and its `.env` variable
- [How it works](docs/how-it-works.md): the pull design, the claim lock and what it does not do
- [Security](docs/security.md): tokens, addresses and what to close on the router
- [Troubleshooting](docs/troubleshooting.md): nothing prints, double sheets, and every message
- [FAQ](docs/faq.md): short answers to common questions

## License

MIT. See [LICENSE](LICENSE).
