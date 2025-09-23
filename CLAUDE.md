# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP library for spam protection that integrates with the StopForumSpam API. It's designed for use in contact forms, comment fields, and forum software to detect and prevent spam submissions.

## Architecture

The library consists of two main classes:

- `Helge\SpamProtection\SpamProtection` (src/SpamProtection.php) - Main class that handles API communication and spam detection logic
- `Helge\SpamProtection\Types` (src/Types.php) - Constants class defining check types (EMAIL, USERNAME, IP)

Key architectural patterns:
- Uses PSR-4 autoloading with namespace `Helge\SpamProtection\`
- Fallback HTTP client implementation (cURL preferred, file_get_contents as fallback)
- Configurable thresholds for frequency and confidence scoring
- Optional Tor exit node detection

## Development Commands

This is a simple PHP library without build tools. Common development tasks:

```bash
# Install dependencies
composer install

# Run the example
php example.php

# Test manually by modifying example.php values
```

## Configuration Options

The main class supports several configuration options:

- **Frequency Thresholds**: THRESHOLD_STRICT (1), THRESHOLD_HIGH (3), THRESHOLD_MEDIUM (5), THRESHOLD_LOW (10)
- **Confidence Thresholds**: CONFIDENCE_STRICT (99), CONFIDENCE_HIGH (80), CONFIDENCE_MEDIUM (40), CONFIDENCE_LOW (10)
- **Tor Node Handling**: TOR_ALLOW (true), TOR_DISALLOW (false)

## API Integration

- Uses StopForumSpam API at `https://api.stopforumspam.org/api`
- API key only required for `submitReport()` functionality
- All check methods return boolean (true = spam detected)
- Supports checking IPs, emails, and usernames

## File Structure

```
src/
├── SpamProtection.php  # Main spam detection class
└── Types.php          # Type constants

example.php             # Usage examples
composer.json          # Package configuration
```

## Testing

No formal test suite is present. Test functionality by:
1. Modifying `example.php` with different values
2. Running `php example.php` to see results
3. Testing with known spam/clean values from StopForumSpam database