# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Infection mutation testing in CI, MSI 100%.

### Changed

- `DbAdapterStorage` and `DbAdapterStorageFactory` now use
  `PhpDb\Adapter\AdapterInterface` and `PhpDb\Sql\Sql` from `php-db/phpdb`
  (`0.6.x-dev`) in place of `laminas/laminas-db`, which is no longer
  required. The class names and the `log.storage.options.adapter` option are
  unchanged; the default adapter service id is now
  `PhpDb\Adapter\Adapter`. See [UPGRADE-2.0.md](UPGRADE-2.0.md).

## [2.0.0] - 2026-10-05

The public API is unchanged. The major version marks the move to PHP 8.3+
and the php-db QA toolchain shared by all Contenir 2.x packages. See
[UPGRADE-2.0.md](UPGRADE-2.0.md).

### Changed

- Requires PHP 8.3, 8.4 or 8.5. PHP 8.1 and 8.2 are no longer supported.
- Requires `laminas/laminas-db` `^2.19`, the first release that supports PHP
  8.3 (was `^2.17`).
- `psr/log` stays at `^1.0 || ^2.0 || ^3.0` and `psr/container` at
  `^1.0 || ^2.0`. `Logger::log()` keeps its natively untyped `$level` and
  `$message` so it remains compatible with psr/log 1.x.
- `DbAdapterStorage::DEFAULT_COLUMNS` is now a typed (`array`) constant.
- Public classes and the `StorageInterface` carry `@api`, and
  `StorageInterface::store()` documents that it throws `RuntimeException`.

### Fixed

- `FilesystemStorage::store()` no longer raises PHP warnings. When `mkdir()`
  lost a race with another worker creating the same log directory, or the
  directory or file could not be written, the warning escaped to the caller;
  under an error handler that converts warnings to exceptions (as Laminas and
  Mezzio applications commonly install), the log call then failed with an
  `ErrorException` instead of succeeding (race) or throwing the documented
  `RuntimeException`. Both calls now run under an error handler scoped to the
  call.

### Added

- Continuous integration on PHP 8.3, 8.4 and 8.5 against lowest, locked and
  latest dependencies, with coverage reported to Codecov.
- Separate unit (no I/O) and integration (real filesystem and SQLite) test
  suites, with 100% line and branch coverage.

### Removed

- `laminas/laminas-coding-standard`, `phpstan/phpstan`, `phpcs.xml` and
  `phpstan.neon`, replaced by Mago via `php-db/phpdb-qa-tools`.

## [0.1.4] - 2026-06-04

### Fixed

- `Logger::log()` is now compatible with psr/log 1.x. The native
  `string|Stringable` type on `$message` narrowed psr/log 1.x's untyped
  `LoggerInterface::log()` parameter, causing a fatal incompatibility error
  whenever the package resolved against psr/log 1.x (the type is retained as a
  PHPStan `@param` annotation).

### Added

- GitHub Actions CI running coding standards, static analysis, and tests across
  PHP 8.1–8.3 with both `--prefer-lowest` and highest dependency resolutions, so
  the full declared `psr/log` range is exercised.

## [0.1.3] - 2026-06-04

### Added

- `DbAdapterStorage` accepts an optional `context` map (config key
  `log.storage.options.context`) that routes individual PSR-3 context entries to
  their own table columns, e.g. `['student' => 'student_id']`. Only context keys
  present on a record are written.

## [0.1.2] - 2026-06-04

### Added

- PHPStan at `level: max` (over `src` and `tests`) and a `composer stan` script.
- `DbAdapterStorageFactory` now validates a configured `columns` map and throws
  when it is not a string-to-string array.

### Changed

- `ConfigProvider::getDefaults()` now declares a precise array shape.

### Fixed

- Type-safety gaps surfaced by static analysis: `Logger::log()` narrows the
  PSR-3 `mixed` level to a string, and the storage factories narrow container
  config defensively instead of relying on blind casts.

## [0.1.1] - 2026-05-27

### Added

- Full unit and integration test suite (100% coverage).

### Changed

- Configuration uses the `log` key with a `storage.adapter` / `storage.options`
  shape, and the storage adapter now defaults to a fully-qualified class name.

## [0.1.0] - 2026-05-27

### Added

- Initial alpha: pluggable PSR-3 logger for Laminas MVC and Mezzio, with
  filesystem and database storage backends.

[2.0.0]: https://github.com/contenir/contenir-log/compare/v0.1.4...HEAD
[0.1.4]: https://github.com/contenir/contenir-log/compare/v0.1.3...v0.1.4
[0.1.3]: https://github.com/contenir/contenir-log/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/contenir/contenir-log/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/contenir/contenir-log/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/contenir/contenir-log/releases/tag/v0.1.0
