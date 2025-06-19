# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-05-28
Initial release v1.0.0
### Added
- Initial release
- Modern PHP 8+ shopping cart implementation
- Multiple storage backends (Session, Cookie, File, Memory)
- Type-safe API with strict typing
- Comprehensive test suite
- PHPStan level 9 compliance
- Fluent API design

### Features
- Cart operations (add, remove, update, clear)
- Item management with custom properties
- Price calculations (subtotal, tax, total)
- Persistence and restoration
- Cart merging and copying
- Export to array/JSON
- Filtering and searching items

### Storage Backends
- SessionStore - Session-based storage
- CookieStore - Cookie-based storage
- FileStore - File system storage
- MemoryStore - In-memory storage

[Unreleased]: https://github.com/vendor/modern-cart/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/vendor/modern-cart/releases/tag/v1.0.0
