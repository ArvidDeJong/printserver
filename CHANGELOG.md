# Changelog

All notable changes to **darvis/printserver** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
