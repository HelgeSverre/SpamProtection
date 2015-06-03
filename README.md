# SpamProtection
PHP Spam Protection Class for use in Contact forms and Comment Fields

[![Get help on Codementor](https://cdn.codementor.io/badges/get_help_github.svg)](https://www.codementor.io/helgesverre)

API Keys to use with this class can be obtained here: http://www.stopforumspam.com/signup

Having an API Key is only neccesary if you are going to use the SubmitReport() function.

## Usage

```php
<?php
require('SpamProtection.php');

// Instantiate a new SpamProtection object
$SpamProtecter = new SpamProtection();

// Set the API key.
$SpamProtecter->SetAPIKey("YOUR-API-KEY"); 

// Display the API key if it exists. (you wouldn't actually do this normally..)
echo $SpamProtecter->GetAPIKey(); 

// Allow or disallow TOR Exit Node IP's
$SpamProtecter->AllowTor(false);

// Check if the IP 8.8.8.8 is in the spam database
if ($SpamProtecter->CheckIP("8.8.8.8")) {
 	die("ACCESS DENIED");
} else {
	// you may enter...
}

// Check if the Email "spam@example.com" is in the spam database.
if ($SpamProtecter->CheckEmail("spam@example.com") {
	die("ACCESS DENIED");
} else {
	// you may enter...
}

// Submit a spam report 
$sent = $SpamProtecter->SubmitReport("vehicle271", "113.116.60.187", "http://pastebin.com/HL9aC5UC", "vehicle271@163.com") {
if ($sent) {
	echo "Spam report has been sent";
} else {
	echo "Could not send spam report, unknown error";
}
?>
```


### Spam Log
If you want to see this script's success rate and how much it's helping me, take a 
look at this logfile: http://helgesverre.com/mail.log all attempts at sending email 
from a blocked IP is saved in this file, ignore entries before 2014-10-06 10:38:21 
they were catched with a manual filter.
