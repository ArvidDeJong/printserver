# Contributing

Contributions are welcome: bug reports, fixes, documentation and ideas.

## Before you start

- **Bugs:** open an [issue](https://github.com/ArvidDeJong/printserver/issues/new/choose) with the steps to reproduce.
- **Features:** open an issue first. This package stays small on purpose, so let's agree a feature fits before you build it.
- **Security issues:** don't open an issue; see [SECURITY.md](SECURITY.md).

## Development

```bash
git clone https://github.com/ArvidDeJong/printserver.git
cd printserver
composer install

composer test      # Pest
composer lint      # Pint, check only (composer format fixes)
composer analyse   # Larastan, level 8
```

CI runs the tests on PHP 8.2-8.4 with Laravel 11, 12 and 13, on the lowest and the latest dependencies.

## Pull requests

- Add or update tests for every change in behaviour.
- Tests must not write into the application directory. `vendor:publish` in a test leaves files
  behind in the Testbench app; assert on `ServiceProvider::pathsToPublish()` instead.
- Read config only in `PrintserverConfig`, and keep the keys of `config/printserver.php` in
  alphabetical order. `tests/Unit/ConfigAccessorTest.php` guards both.
- Keep the public API compatible within 1.x: the `PrintQueue` methods, the four endpoints, the
  three commands and the columns of `print_jobs`.
- A claimed job must never be handed out twice and a failed job must never go back to pending.
  `tests/Feature/EndpointsTest.php` pins that; don't relax it.
- Write code, comments and messages in English.
- Update `docs/`, `CHANGELOG.md` (under `Unreleased`) and `resources/boost/` when users will
  notice the change.
- The documentation in `docs/` is also the website. Don't write `{{ }}` or `{% %}` there; Jekyll
  would render it.

## Code of conduct

This project follows the [Contributor Covenant](https://github.com/ArvidDeJong/.github/blob/main/CODE_OF_CONDUCT.md).
