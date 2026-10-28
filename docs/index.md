---
title: "Home"
nav_order: 1
description: "darvis/printserver queues print jobs in Laravel and lets a print box on location fetch them over HTTPS, so printers stay off the internet."
permalink: /
---

# Printserver

`darvis/printserver` is a Laravel package for printing on a printer that stands somewhere else:
a branch, a workshop, a customer's site. Your application queues a job. A small box on location
asks the application every few seconds whether there is work, fetches the file over HTTPS and
prints it on its own network.

All traffic is outgoing from that location. The printers are not reachable from the internet, and
nothing has to be opened on the router there.

## Why not send to the printer directly

A printer behind a port forward on port 9100 prints whatever is sent to it. There is no login and
no check on the sender. Port scanners find such a port in days, and the printer starts producing
pages nobody asked for. Closing that forward is the point of this package.

## What it does not do

- It does not drive the printer itself. CUPS on the box does that, and any printer CUPS supports
  works, label printers included.
- It does not render documents. You queue the bytes: a PDF, or the raw language of a label printer.
- It does not retry a failed job. A job that failed stays failed on purpose.
- It does not give you a user interface. It gives you a service, four endpoints and three commands.

## Requirements

- PHP 8.2 or higher
- Laravel 11, 12 or 13
- A database and a filesystem disk
- A box on location: a mini pc or a Raspberry Pi with Linux, CUPS and the agent

## Install

```bash
composer require darvis/printserver
php artisan migrate
php artisan print:token amsterdam
```

[Installation](installation.md) has the full steps, for the application and for the box.

## All pages

- [Installation](installation.md): the application, the box on location, and how to check it works
- [Queueing jobs](queueing.md): the service, the formats and what the printer receives
- [The box on location](printbox.md): the agent, its configuration and the CUPS queues
- [Configuration](configuration.md): every setting with its environment variable
- [How it works](how-it-works.md): the pull design, the claim lock and the statuses
- [Security](security.md): tokens, allowed addresses and what to close on the router
- [Troubleshooting](troubleshooting.md): nothing prints, double sheets, and every message with its cause
- [FAQ](faq.md): short answers to common questions
