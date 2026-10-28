---
title: "How it works"
nav_order: 6
description: "The pull design of darvis/printserver: the four endpoints, the lock that stops a job printing twice, the statuses and the limits of the approach."
---

# How it works

## The path of one job

1. Your application calls `PrintQueue::queue()`. The content goes to the disk, a row goes into
   `print_jobs` with the status `pending` and the administration of this installation.
2. The box on location posts to `print/claim` with its token. Inside one database transaction the
   waiting jobs are selected with a lock and marked `claimed`, with the name of the box in
   `claimed_by`.
3. The box fetches each job from `print/file/{uuid}` and hands the bytes to its CUPS queue.
4. The box posts to `print/done/{uuid}`. The job becomes `printed` and the file is dropped. Or it
   posts to `print/failed/{uuid}` with the reason, and the job becomes `failed`.
5. `print:cleanup` removes rows and files older than `keep_days`.

## Why the box asks instead of the server sending

A port that reaches a printer reaches everyone. Printers on port 9100 accept anything, with no
login and no sender check, and a scanner finds such a port within days. Here every connection is
made by the box, outwards, over HTTPS, with a token. There is nothing to find and nothing to open.

The price is latency: a job waits at most one poll interval, three seconds in practice. For a
counter printing a packing slip that is invisible.

## The lock, and why a failed job stays failed

`claim` selects and marks in the same transaction with `lockForUpdate`. Without that lock a second
box, or a second round of the same box, could take the same job again and the sheet would come out
twice.

For the same reason a failed job is never put back on `pending`. The box reports a failure after it
has handed the job to CUPS, and it cannot know whether the printer already produced something.
Better a sheet that does not come out, which someone notices, than one that comes out twice, which
nobody does.

## The statuses

| Status | Meaning | What it tells you when it stays that way |
| --- | --- | --- |
| `pending` | Waiting | The box never came by: it is off, has no network, or points somewhere else |
| `claimed` | Taken by a box | The box took it but never reported back: it stopped halfway, or lost its connection |
| `printed` | Handed to the printer | Nothing. The sheet may still be stuck in the printer itself |
| `failed` | Refused | The `error` column holds what CUPS said |

`printed` means the box gave the job to CUPS without an error, nothing more. A printer that is out
of paper still reports a successful hand-off.

## What it does not do

- It does not render documents. You queue the bytes.
- It does not drive printers. CUPS on the box does, and an unsupported printer stays unsupported.
- It does not retry. Queue the job again yourself when you want a second attempt.
- It does not notice a printer that is paused, out of paper or offline. The box reports what CUPS
  tells it, and CUPS happily accepts a job for a paused queue.
