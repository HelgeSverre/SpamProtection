# SpamProtection
#### PHP Spam Protection Class for use in Contact forms and Comment Fields


API Keys to use with this class can be obtained here: http://www.stopforumspam.com/signup


## Usage

```
require('SpamProtection.php');

// Instantiate a new SpamProtection object
$SpamProtecter = new SpamProtection();

$SpamProtecter->SetAPIKey("YOUR-API-KEY"); 

// Allow or disallow TOR Exit Node IP's
$SpamProtecter->AllowTor(false);

// check if the IP 8.8.8.8 is in the spam database
echo $SpamProtecter->CheckIP("8.8.8.8");

// check if the Email "helge.sverre@gmail.com" is in the spam database.
echo $SpamProtecter->CheckEmail("helge.sverre@gmail.com");

```