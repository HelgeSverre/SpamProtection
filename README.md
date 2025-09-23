# SpamProtection

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]
![CI Status](https://github.com/HelgeSverre/SpamProtection/actions/workflows/ci.yml/badge.svg)

A modern PHP 8.1+ spam protection library using the StopForumSpam API for contact forms and comment fields.

**Version 2.0** brings modern PHP features including strict typing, enums, custom exceptions, and comprehensive testing.

API Keys can be obtained here: https://www.stopforumspam.com/signup (only required for the submitReport() function)

## Requirements

- PHP 8.1 or higher
- ext-curl
- ext-json

## Install

``` bash
composer require helgesverre/spamprotection
```



## Usage

Example.php

```php
<?php

declare(strict_types=1);

use Helge\SpamProtection\SpamProtection;
use Helge\SpamProtection\Types;
use Helge\SpamProtection\Exception\ApiCheckException;

require 'vendor/autoload.php';

$spamProtector = new SpamProtection();


/** 
 * All checks can be called from the check() method, 
 * first param is the type of check, second is the value (ip, email, username)
 */
var_dump($spamProtector->check(Types::EMAIL, "helge.sverre@gmail.com"));
var_dump($spamProtector->check(Types::IP, "8.8.8.8"));
var_dump($spamProtector->check(Types::USERNAME, "helgesverre"));

/**
 * For convenience some wrapper methods are provided, 
 * they simply pass the data long to check()
 */
var_dump($spamProtector->checkEmail("helge.sverre@gmail.com"));
var_dump($spamProtector->checkIP("8.8.8.8"));
var_dump($spamProtector->checkUsername("spammer"));

```
the check methods returns a bool, where TRUE means the IP/Email/Username has been flagged as spam.

If you want to specify a "threshold" of how many spam reports are needed before something is flagged as spam you can use the ```setFrequencyThreshold()``` method or specify a threshold in the constructor:

```php
<?php
use Helge\SpamProtection\SpamProtection;
use Helge\SpamProtection\Types;

require 'vendor/autoload.php';

/*
    The following constants are available in the spamprotection class for convenience
    const THRESHOLD_STRICT = 1;
    const THRESHOLD_HIGH = 3;
    const THRESHOLD_MEDIUM = 5;
    const THRESHOLD_LOW = 10;
*/

$spamProtector = new SpamProtection(SpamProtection::THRESHOLD_STRICT);

// or 

$spamProtector = new SpamProtection();
$spamProtector->setFrequencyThreshold(SpamProtection::THRESHOLD_HIGH);

```

The library can also flag Tor Exit Nodes as spam when they are encountered.

To enable this use the ```setAllowTorNodes($allowTorNodes)``` to true or false, or specify it as the second param in the constructor:

```php
<?php
use Helge\SpamProtection\SpamProtection;
use Helge\SpamProtection\Types;

require 'vendor/autoload.php';

/*
    The following constants are available in the spamprotection class for convenience
    const TOR_ALLOW = true;
    const TOR_DISALLOW = false;
*/

$spamProtector = new SpamProtection(SpamProtection::THRESHOLD_STRICT, SpamProtection::TOR_DISALLOW);

// or 

$spamProtector = new SpamProtection();
$spamProtector->setAllowTorNodes(false);
```

## Exception Handling

Version 2.0 introduces custom exceptions for better error handling:

```php
use Helge\SpamProtection\Exception\ApiCheckException;
use Helge\SpamProtection\Exception\ApiKeyMissingException;
use Helge\SpamProtection\Exception\SubmissionFailedException;

try {
    $isSpam = $spamProtector->checkEmail("test@example.com");
} catch (ApiCheckException $e) {
    // Handle API connection or response errors
    error_log("API check failed: " . $e->getMessage());
}

try {
    $spamProtector->submitReport($username, $ip, $evidence, $email);
} catch (ApiKeyMissingException $e) {
    // API key is required but not configured
} catch (SubmissionFailedException $e) {
    // Submission to StopForumSpam failed
}
```

## Development

### Available Commands

This project includes both Composer scripts and a Makefile for common development tasks:

#### Testing

```bash
# Run all tests
composer test
# or
make test

# Run tests with code coverage
composer test:coverage
# or
make test-coverage

# Run only unit tests
composer test:unit
# or
make test-unit

# Run only integration tests
composer test:integration
# or
make test-integration
```

#### Code Quality

```bash
# Check code style (no changes)
composer lint
# or
make lint

# Fix code style automatically
composer format
# or
make format

# Run static analysis with PHPStan
composer phpstan
# or
make phpstan

# Run all QA checks (lint, static analysis, tests)
composer qa
# or
make qa

# Run QA with automatic fixes where possible
composer qa:fix
# or
make qa-fix
```

#### Other Commands

```bash
# Install dependencies
make install

# Update dependencies
make update

# Clean build artifacts
make clean

# Show all available make commands
make help
```

## Migrating from v1.x to v2.0

### Breaking Changes

1. **PHP Version**: Requires PHP 8.1 or higher
2. **Types Class**: Now an enum instead of a class with constants
3. **Type Declarations**: All methods have strict type declarations
4. **Exceptions**: Custom exceptions replace generic \Exception

### Migration Example

```php
// v1.x
$spamProtector->check("email", "test@example.com");

// v2.0
$spamProtector->check(Types::EMAIL, "test@example.com");
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/helgesverre/spamprotection.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/helgesverre/spamprotection.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/helgesverre/spamprotection
[link-downloads]: https://packagist.org/packages/helgesverre/spamprotection
[link-author]: https://github.com/helgesverre