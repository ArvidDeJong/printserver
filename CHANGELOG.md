# Changelog

All notable changes to **darvis/printserver** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- First release. Queue print jobs in Laravel and let a box on location fetch them over HTTPS:
  the `print_jobs` table, the `PrintQueue` service, the four endpoints behind a bearer token,
  the `print:token`, `print:test` and `print:cleanup` commands, and an administration lock so a
  box of one branch can never pick up the work of another.
