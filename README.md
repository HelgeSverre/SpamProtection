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

$spamProtector = new SpamProtection(SpamProtection::THRESHOLD_STRICT, SpamProtection::TOR_DISALLOW);


/** 
 * All checks can be called form the check() method, 
 * first param is the type of check, second is the value (ip, email, username)
 */
var_dump($spamProtector->check(Types::EMAIL, "helge.sverre"));
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



# License

MIT licenced