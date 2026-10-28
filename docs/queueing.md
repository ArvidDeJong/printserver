---
title: "Queueing jobs"
nav_order: 3
description: "Queue print jobs from a Laravel application with the PrintQueue service: printers, formats, titles and what the printer on location receives."
---

# Queueing jobs

Everything goes through one service. It is the only place a job is created.

```php
use Darvis\Printserver\Services\PrintQueue;

public function print(Order $order, PrintQueue $queue): void
{
    $queue->queue(
        printer: 'PRINTER01',
        content: Pdf::loadView('orders.packing-slip', ['order' => $order])->output(),
        title: 'Packing slip '.$order->number,
    );
}
```

## The arguments

| Argument | What it is |
| --- | --- |
| `printer` | The key of the printer. It is also the name of the queue in CUPS on the box, so keep them identical. |
| `content` | The raw bytes. A PDF, or the language of a label printer. |
| `title` | Free text, shown in the log of the box and in the `print_jobs` table. Optional. |
| `format` | `pdf` (the default) or `zpl`. It only decides the extension of the stored file and tells the box what it is holding. |

The content is written to the configured disk and the row keeps the path, so a large document does
not end up in the database.

## Label printers

A label printer gets its own language, not a PDF. Send the ZPL as it is:

```php
$queue->queue('ZEBRA01', "^XA^FO50,50^A0N,40^FDOrder 12345^FS^XZ", 'Label 12345', 'zpl');
```

The box sends raw content straight to the queue, so the printer receives exactly these bytes.

## A test page

```php
$queue->queueTestPage('PRINTER01');
```

That queues a PDF with the printer, its size, the administration and the time on it, the same
thing `php artisan print:test PRINTER01` does. The page has the size of the paper or label from
the [configuration](configuration.md#printers-and-their-sizes), with a border just inside the edge:
on a calibrated label printer that border runs around all four sides. The PDF is built by hand, so
a test page needs no pdf library.

A label printer that is fed PDF needs to accept PDF itself; Zebra calls this PDF Direct. Without
it, send it a test in its own language instead:

```php
$queue->queue('ZEBRA01', "^XA^FO40,40^A0N,40^FDTest ZEBRA01^FS^XZ", 'Test ZEBRA01', 'zpl');
```

## What happens next

The job gets the status `pending` and the administration of this installation. The box claims it,
fetches the file, prints it and reports back; [How it works](how-it-works.md) follows that path
step by step. Your application does not wait for any of it.

## Reading back what happened

```php
use Darvis\Printserver\Models\PrintJob;

PrintJob::where('status', PrintJob::STATUS_FAILED)->latest()->get();
PrintJob::where('uuid', $uuid)->first()->claimed_by;   // the name of the box
```

The statuses are `pending`, `claimed`, `printed` and `failed`, as constants on the model. Rows stay
as history until `print:cleanup` removes them; the file itself is dropped as soon as the job is
printed.
