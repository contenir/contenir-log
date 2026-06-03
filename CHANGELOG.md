# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[0.1.2]: https://github.com/contenir/contenir-log/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/contenir/contenir-log/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/contenir/contenir-log/releases/tag/v0.1.0
