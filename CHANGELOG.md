# Changelog

## [2.0.0] - 2026-10-15

### Breaking changes
- Require PHP 8.1+, ext-curl, and ext-json.
- Replace the `Types` constants class with a string-backed enum. Pass `Types::EMAIL`,
  `Types::IP`, or `Types::USERNAME` to `check()` instead of a string.
- Add strict parameter, property, and return types.
- Throw custom exceptions for API checks, missing API keys, and failed submissions.
- Throw on API rejections and malformed spam scores instead of treating them as clean.

### Added
- Descriptive API failure messages with chained transport and JSON exceptions.
- Deterministic PHPUnit tests for spam decisions, thresholds, URL encoding, errors,
  submission responses, and both cURL and stream transports.
- Separate read-only live API smoke tests.
- Use patched PHPUnit 10.5.62+ in place of Pest 2, which pins an affected PHPUnit version.
- GitHub Actions checks on PHP 8.1 through 8.5, PHPStan 2 level 8, and PHP CS Fixer.
- Composer scripts and Makefile targets for development checks.

### Changed
- Submit spam reports over HTTPS and request structured JSON responses, while
  retaining support for the original HTML success response.
- Bound HTTP request duration and report connection failures and HTTP error codes.
- Redact the configured API key from returned API error messages.
- Remove the hardcoded Composer version and release time so metadata comes from Git.

### Fixed
- Test configuration referenced an absent `tests/Feature` directory.
- Default tests and CI depended on changing live spam records.

### Contributors
- Raimondas Rimkevičius (MekDrop): PHP 8.1 modernization (#7), Composer version fix
  (#8), and custom exceptions (#9). Original commits are retained in Git history.

## [1.2.0] - 2019-06-26

### Added
- Confidence threshold support (#6).

## [1.1.1] - 2019-01-02

### Changed
- Use HTTPS for API checks (#4). The 1.1.0 and 1.1.1 tags point to the same commit.

## [1.0.1] - 2018-07-30

- Published the 1.0.1 release on GitHub; its tag points to a 2015 commit.

## [1.0.0] - 2015-10-23

### Added
- Initial release: IP, email, and username checks, frequency thresholds, Tor exit
  node handling, and spam report submission.

[2.0.0]: https://github.com/HelgeSverre/SpamProtection/compare/1.2.0...2.0.0
[1.2.0]: https://github.com/HelgeSverre/SpamProtection/releases/tag/1.2.0
[1.1.1]: https://github.com/HelgeSverre/SpamProtection/releases/tag/1.1.1
[1.0.1]: https://github.com/HelgeSverre/SpamProtection/releases/tag/1.0.1
[1.0.0]: https://github.com/HelgeSverre/SpamProtection/releases/tag/1.0.0
