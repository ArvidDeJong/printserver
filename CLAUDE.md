# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository. The conventions shared by every darvis package (language, releases, CI, docs site, Boost guidelines, public API policy) are in [../CLAUDE.md](../CLAUDE.md); this file only holds what is specific to this package.

## Package overview

`darvis/printserver` is a Laravel package (PHP 8.2+, Laravel 11/12/13) that queues print jobs and lets a box on location fetch them over HTTPS, so printers never sit behind a port forward. It grew out of CoatR (`~/Sites/coatrcloud`) and the test app `~/Sites/labs.qoat.eu`; both are meant to consume it.

- Namespace: `Darvis\Printserver\` → `src/`
- Service provider auto-registered via `extra.laravel.providers` in [composer.json](composer.json)
- Config key: `printserver`
- The agent that runs on the box (Python, CUPS, systemd) is not part of this repository. It lives in the Qoat labs folder on OneDrive (`projecten/qoat/labs/printserver/printbox`).

## Architecture

- [PrintserverConfig](src/Support/PrintserverConfig.php) is the only place that reads the package config. `tests/Unit/ConfigAccessorTest.php` guards that.
- [PrintQueue](src/Services/PrintQueue.php) is the only place a job is created. The content goes to the disk, the row keeps the path.
- [PrintJobController](src/Http/Controllers/PrintJobController.php) holds the four endpoints. `claim()` selects and marks in one transaction with `lockForUpdate()`; never split that, or a job prints twice. `failed()` never sets a job back to `pending`, on purpose.
- [VerifyPrintboxToken](src/Http/Middleware/VerifyPrintboxToken.php) checks the address first, then the token with `hash_equals`, then the administration. It puts the name of the box in the `printbox` request attribute, which ends up in `claimed_by`.
- The routes read the prefix and the middleware when they are registered. Tests that change those set `$this->extraConfig` and call `refreshApplication()`.
- The endpoint urls and the json shape are the contract with the agent on the box. Changing them breaks every box in the field: a major release.
