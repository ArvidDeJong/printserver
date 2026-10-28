# Changelog

All notable changes to **darvis/printserver** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-10-28

### Added
- One installation can serve several locations. A printer in `printserver.printers` can name its
  own `administration`; its jobs get that administration and only a box of it receives them. A
  box of any administration the installation or its printers name gets in.
- A printer can name the `printer` it is known by on its box, so two locations can each have a
  printer called `konika` in one list.
- `PrintserverConfig::administrations()`, `printerAdministration()` and `printerOnBox()`.

### Changed
- `file`, `done` and `failed` answer `404` for a job of another administration, so a box can
  only touch its own jobs, also when it knows the uuid of another.
- A box without an administration only gets the jobs of the installation's own administration.

## [1.1.0] - 2026-10-28

### Added
- A printer in `printserver.printers` can be an array with a `name` and a `size`: `a4`, `4x2`,
  `4x4` or `4x6`. A test page now gets the size of the paper or label of its printer, so a
  label printer receives a test label instead of an A4.
- `PrintserverConfig::printerSize()`.

### Changed
- A test page shows the printer, its name, its size, the administration and the time, with a
  border just inside the edge to check the calibration of a label printer.
- Accents on a test page print as accents.

## [1.0.0] - 2026-10-28

### Added
- First release. Queue print jobs in Laravel and let a box on location fetch them over HTTPS:
  the `print_jobs` table, the `PrintQueue` service, the four endpoints behind a bearer token,
  the `print:token`, `print:test` and `print:cleanup` commands, and an administration lock so a
  box of one branch can never pick up the work of another.
