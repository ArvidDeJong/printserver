---
title: "Troubleshooting"
nav_order: 8
description: "Nothing prints, a sheet comes out twice, jobs stay claimed, a token is refused: every symptom of darvis/printserver with its cause and its fix."
---

# Troubleshooting

Start at the job itself. The status says which half of the chain to look at:

```php
Darvis\Printserver\Models\PrintJob::latest()->first();
```

## Nothing prints
{: #nothing-prints }

**The job stays `pending`.** The box never came by. Check, on the box:

```bash
systemctl status printbox
journalctl -u printbox -n 50
curl -s -o /dev/null -w '%{http_code}\n' -X POST \
  -H "Authorization: Bearer <token>" https://example.com/print/claim
```

A `200` means the chain is fine and the agent is the problem. A `401` means the token does not
match: it was regenerated, or the application still has the old line cached. Run
`php artisan config:clear` after every change to `.env`. A `403` means the address or the
administration, see below. No answer at all means the box has no route out, or no DNS.

**The job stays `claimed`.** The box took the job and never came back. Its log says where it
stopped: a printer that does not answer, a queue that does not exist, or the agent that was
restarted mid-job. The job is not handed out again; queue it again after you fixed the cause.

**The job is `failed`.** The `error` column holds what CUPS said. `lpstat -p` on the box shows the
queues; a queue CUPS does not know gives `The printer or class does not exist`.

**The job is `printed` but no sheet.** The box handed the job to CUPS without an error, so the
problem sits in the printer or in the queue. A paused queue swallows jobs silently: `lpstat -p`
says `disabled` and `cupsenable <printer>` resumes it. A label printer that was paused at the
device itself keeps its labels until someone presses resume there.

## A sheet comes out twice
{: #double-sheets }

Two boxes with the same token, both claiming the same jobs. A token belongs to one box. Give each
box its own, and check `claimed_by` on the rows: two names on jobs you expected on one box proves
it.

## A token is refused
{: #a-token-is-refused }

- `No valid token.` The token does not appear in `PRINTSERVER_TOKENS`, or the config is cached.
  `php artisan config:clear`, and `php artisan print:token --show` lists the boxes that do have
  one. A lost token cannot be read back; `php artisan print:token <name>` makes a new one.
- `This token belongs to administration rotterdam, this installation is amsterdam.` The box points at the
  wrong installation, which is exactly what the administration is for. Point it at the right url,
  or generate a token for this installation.
- `This address is not allowed here.` The address of the location is not in
  `PRINTSERVER_ALLOWED_IPS`. Behind a proxy, check `TrustProxies` first: without it every request
  appears to come from the proxy.

## The file is gone
{: #the-file-is-gone }

`This file has already been cleaned up.` with a `410`: the job was printed, or `print:cleanup`
passed. The file is dropped as soon as a job is reported as printed, so a box that asks for it
again gets this. Queue a new job.

## The disk fills up
{: #the-disk-fills-up }

`print:cleanup` is not scheduled. Add `Schedule::command('print:cleanup')->daily();` to
`routes/console.php`. Run it once by hand to clear the backlog: `php artisan print:cleanup --days=1`.

## Nothing is cached, nothing is wrong, still nothing works

Queue a test page straight from the server and watch both ends:

```bash
php artisan print:test PRINTER01
```

If that sheet comes out, the chain is fine and the problem is in the code that queues jobs: check
the printer key, which must be identical to the name of the queue in CUPS, including its case.
