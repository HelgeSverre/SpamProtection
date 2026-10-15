# Project guidance

SpamProtection is a PHP 8.1+ library for StopForumSpam checks and report submissions.

- `src/SpamProtection.php`: thresholds, URL building, HTTP requests, and response handling.
- `src/Types.php`: string-backed enum for IP, email, and username checks.
- `src/Exception/`: API check, API key, and submission exceptions.
- `tests/Unit/`: deterministic responses and a local HTTP server; no external API calls.
- `tests/Integration/`: read-only smoke checks against the live API.

Run `composer install`, then `composer qa` (format check, PHPStan, deterministic tests).
Use `composer format` to apply formatting. `composer test:integration` runs live
checks; `composer test:all` runs both suites. Never submit test reports to the live
spam database. API keys are only required for `submitReport()`.

Use `Types::IP`, `Types::EMAIL`, or `Types::USERNAME` with `check()`. A `true` result
means spam. API failures throw custom exceptions, preserving underlying causes.

Keep Composer version and time fields unset: releases derive their metadata from
Git tags. Credit external contributions and preserve their original commits.
