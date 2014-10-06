<?php

/**
 * Class used for spam prevention, primarily in contact forms, but can also be used in forum software and comment fields.
 *
 * @author  Helge Sverre <email@helgesverre.com>
 *
 * @return object SpamProtection object
 *
 */
class SpamProtection {

	private $AllowTor = false;
	private $APIUrl = "http://api.stopforumspam.org/api?f=json";
	private $APIKey;


	/**
	 * Internal function used to query the StopForumSpam API for an IP address.
	 *
	 * @author  Helge Sverre <email@helgesverre.com>
	 *
	 * @param string $IP the IP address to search the API for.
	 * @return object returns a json decoded object, used by the public CheckIP function.
	 */
	private function APICheckIP($IP) {
		$FullAPIURL = $this->APIUrl . "&ip=" . $IP;
		
		if ($this->AllowTor) {
			$FullAPIURL .= "&amp;notorexit";
		}

		$json = file_get_contents($FullAPIURL);
		
		if(!$json === false) {
			return json_decode($json);
		} else {
			throw new Exception("API Check Unsuccsessfull");
		}
	}


	/**
	 * Internal function used to query the StopForumSpam API for an Email address.
	 *
	 * @author  Helge Sverre <email@helgesverre.com>
	 *
	 * @param string $Email the Email address to search the API for.
	 * @return object returns a json decoded object, used by the public CheckEmail function.
	 */
	private function APICheckEmail($Email) {
		
		$FullAPIURL = $this->APIUrl . "&email=" . $Email;
		
		if ($this->AllowTor) {
			$FullAPIURL .= "&amp;notorexit";
		}

		$json = file_get_contents($FullAPIURL);

		if(!$json === false) {
			return json_decode($json);
		} else {
			throw new Exception("Could not fetch contents of URL");
		}
	}


	/**
	 * Function used to query the StopForumSpam API for an IP address and return if it is registered as a spamer IP.
	 *
	 * @author  Helge Sverre <email@helgesverre.com>
	 *
	 * @param string $IP the IP address to search the API for.
	 * @return bool true means the IP is a spammy ip, if false, it's not, throws an exception on failure
	 */
	public function CheckIP($IP) {
		
		$Result = $this->APICheckIP($IP);

		if ($Result->success == 1) {
			return $Result->ip->appears;
		} else {
			throw new Exception("Error: " . $Result->error);
		}
	}


	/**
	 * Function used to query the StopForumSpam API for an Email address and return if it is registered as a spam email.
	 *
	 * @author  Helge Sverre <email@helgesverre.com>
	 *
	 * @param string $Email the Email address to search the API for.
	 * @return bool true means the IP is a spammy email, if false, it's not, throws an exception on failure
	 */
	public function CheckEmail($Email) {
		
		$Result = $this->APICheckEmail($Email);

		if ($Result->success == 1) {
			return $Result->email->appears;
		} else {
			throw new Exception("Error: " . $Result->error);
		}
	}


	/**
	 * Function used to submit a spam report to StopForumSpam,
	 *
	 * @author  Helge Sverre <email@helgesverre.com>
	 *
	 * @param string $username Username of the spammer.
	 * @param string $ip the ip of the spammer
	 * @param string $evidence evidence of spam, usually you pass it a copy of the original email(with all the headers etc).
	 * @param string $email the spammers email.
	 * @return bool returns true if report was submitted, exception on failure.
	 */
	public function SubmitReport($username, $ip, $evidence, $email) {
		$Request = "http://www.stopforumspam.com/add.php"
		. "?username=" 	. urlencode($username)
		. "&ip_addr=" 	. urlencode($ip)
		. "&evidence=" 	. urlencode($evidence)
		. "&email="	 	. urlencode($email)
		. "&api_key=" 	. urlencode($this->APIKey);

		die($Request);
		//$Response = file_get_contents($Request);

		if (!strpos($Response, "data submitted successfully") === FALSE) {
			return true;
		} else {
			throw new Exception("Submission failed.");
		}
	}


	/**
	 * Function used to enable or disable blocking of TOR Exit node IP's, disabled by default
	 *
	 * @author  Helge Sverre <email@helgesverre.com>
	 *
	 * @param bool $bool wether or not you want tor users to be flagged as spammers.
	 */
	public function AllowTor($bool) {
		$this->AllowTor = $bool;
	}


	/**
	 * Function used to set your API key, this is only neccesary if you need the ability to submit spam reports.
	 *
	 * @author  Helge Sverre <email@helgesverre.com>
	 *
	 * @param string $APIKey your API key, can be obtained from here: http://www.stopforumspam.com/signup
	 */
	public function SetAPIKey($APIKey) {
		$this->APIKey = $APIKey;
	}


	/**
	 * returns the api key
	 *
	 * @author  Helge Sverre <email@helgesverre.com>
	 *
	 * @return string the set api key, throws an exception on failure.
	 */
	public function GetAPIKey() {
		if (isset($this->APIKey)){
			return $this->APIKey;	
		} else {
			throw new Exception("API Key not set, cannot return empty api key.");
		}
	}
}
?>