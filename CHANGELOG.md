# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025

### Breaking Changes
- Minimum PHP version requirement raised to 8.1
- `Types` class converted to PHP enum
- All public methods now have strict type declarations
- Custom exceptions replace generic \Exception throws
- Method signatures updated with proper type hints

### Added
- Custom exception classes for better error handling:
  - `ApiCheckException` - thrown when API check fails
  - `ApiKeyMissingException` - thrown when API key is required but missing
  - `SubmissionFailedException` - thrown when spam report submission fails
- PHPUnit test suite with comprehensive test coverage
- GitHub Actions CI/CD pipeline for automated testing
- Support for PHP 8.1, 8.2, and 8.3
- PHPStan static analysis at level 8
- PHP CS Fixer for code style consistency
- Composer scripts for testing and code quality checks

### Changed
- Types class is now an enum with proper type safety
- All methods have strict type declarations and return types
- Properties have proper type declarations
- Constants changed from `const` to `public const`
- Submit report endpoint uses HTTPS instead of HTTP
- Improved PHPDoc blocks with accurate exception annotations

### Removed
- Hardcoded version from composer.json
- Support for PHP < 8.1
- TODO comment about "last seen" cutoff (moved to issues)

### Fixed
- Composer validation warnings
- Security: submitReport now uses HTTPS endpoint
- Improved error handling with specific exceptions

## [1.1.0] - 2019
### Added
- Confidence threshold support (PR #6)

## [1.0.1] - 2018
### Changed
- API requests now use HTTPS (PR #4)

## [1.0.0] - 2015
### Added
- Initial release
- Support for checking IPs, emails, and usernames
- Frequency threshold configuration
- Tor exit node detection
- Spam report submission