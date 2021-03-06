<?php

use Helge\SpamProtection\SpamProtection;
use Helge\SpamProtection\Types;

require 'vendor/autoload.php';

$spamProtector = new SpamProtection(SpamProtection::THRESHOLD_STRICT, SpamProtection::TOR_DISALLOW);

var_dump($spamProtector->check(Types::EMAIL, "helge.sverre"));

var_dump($spamProtector->check(Types::IP, "8.8.8.8"));

var_dump($spamProtector->check(Types::USERNAME, "helgesverre"));


var_dump($spamProtector->checkEmail("helge.sverre@gmail.com"));

var_dump($spamProtector->checkIP("8.8.8.8"));

var_dump($spamProtector->checkUsername("spammer"));

var_dump($spamProtector->checkUsername("spammer2"));

var_dump($spamProtector->checkUsername("spammer3"));

var_dump($spamProtector->checkUsername("spammer4"));


/* Confidence Threshold */

// If the frequency is less than the frequency threshold - Return as not spam
// If the frequency is more than the frequency threshold, but the confidence is less than the confidence threshold - Return as not spam
// If the frequency is more than the frequency threshold and the confidence is more than the confidence threshold - Return as spam
$spamProtector = new SpamProtection(SpamProtection::THRESHOLD_STRICT, SpamProtection::TOR_DISALLOW, null, SpamProtection::CONFIDENCE_LOW);