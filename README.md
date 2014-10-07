# SpamProtection
#### PHP Spam Protection Class for use in Contact forms and Comment Fields


API Keys to use with this class can be obtained here: http://www.stopforumspam.com/signup
Having an API Key is only neccesary if you are going to use the SubmitReport() function.

## Usage

```php
require('SpamProtection.php');

// Instantiate a new SpamProtection object
$SpamProtecter = new SpamProtection();

// Set the API key.
$SpamProtecter->SetAPIKey("YOUR-API-KEY"); 

// Display the API Key.
$SpamProtecter->GetAPIKey(); 

// Allow or disallow TOR Exit Node IP's
$SpamProtecter->AllowTor(false);

// Check if the IP 8.8.8.8 is in the spam database
echo $SpamProtecter->CheckIP("8.8.8.8");

// Check if the Email "helge.sverre@gmail.com" is in the spam database.
echo $SpamProtecter->CheckEmail("helge.sverre@gmail.com");

```


### Spam Log
If you want to see this script's success rate and how much it's helping me, take a 
look at this logfile: http://helgesverre.com/mail.log all attempts at sending email 
from a blocked IP is saved in this file, ignore entries before 2014-10-06 10:38:21 
they were catched with a manual filter.
