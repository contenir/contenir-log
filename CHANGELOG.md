# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[0.1.4]: https://github.com/contenir/contenir-log/compare/v0.1.3...v0.1.4
[0.1.3]: https://github.com/contenir/contenir-log/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/contenir/contenir-log/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/contenir/contenir-log/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/contenir/contenir-log/releases/tag/v0.1.0
