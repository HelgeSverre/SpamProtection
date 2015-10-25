# SpamProtection
PHP Spam Protection Class for use in Contact forms and Comment Fields

API Keys to use with this class can be obtained here: http://www.stopforumspam.com/signup

Having an API Key is only necessary if you are going to use the submitReport() function.

## Installation 

```
composer require helgesverre/spamprotection
```



## Usage

Example.php

```php
<?php
use Helge\SpamProtection\SpamProtection;
use Helge\SpamProtection\Types;

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


# License

MIT licenced
