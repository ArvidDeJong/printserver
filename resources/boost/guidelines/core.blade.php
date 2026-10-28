## darvis/printserver

Print on printers at another location without opening a port there. The application queues a job; a box on location polls the endpoints over HTTPS, fetches the file and prints it through its own CUPS.

- Queue every job through `Darvis\Printserver\Services\PrintQueue`: `queue(string $printer, string $content, ?string $title = null, string $format = 'pdf')`. Never create a `PrintJob` row yourself: the content belongs on the configured disk, not in the database.
- `$printer` is the key of the printer and must be identical, case included, to the name of the CUPS queue on the box.
- Send label printers their own language (`zpl`), not a PDF. A PDF is for printers with a driver.
- A claimed job is never handed out again and a failed job is never put back on `pending`. Don't build a retry that changes the status back; queue a new job instead.
- Tokens are `name:administration:token` in `PRINTSERVER_TOKENS`, comma separated. Generate them with `php artisan print:token <name>`, which writes the entry into the `.env`; never write a token in code, config or a test fixture of the host app.
- With more than one location, set `PRINTSERVER_ADMINISTRATION`. A box only receives the jobs of its own administration. One installation that serves several locations gives each printer in `printserver.printers` an `administration`, and a `printer` when the box knows it by another name.
- Read settings through `Darvis\Printserver\Support\PrintserverConfig`, not `config('printserver.…')`.
- Schedule `print:cleanup` daily. Use `php artisan print:test <printer>` to check a box end to end.

@verbatim
<code-snippet name="Queue a packing slip and a label" lang="php">
use Darvis\Printserver\Services\PrintQueue;

public function print(Order $order, PrintQueue $queue): void
{
    $queue->queue('PRINTER01', $pdf, 'Packing slip '.$order->number);
    $queue->queue('ZEBRA01', $zpl, 'Label '.$order->number, 'zpl');
}
</code-snippet>
@endverbatim
