<?php

namespace Helge\SpamProtection;

// TODO(25 okt 2015) ~ Helge: Add support for setting a certaincy threshold
// TODO(25 okt 2015) ~ Helge: add support for a "last seen" cutoff

/**
 * Class used for spam prevention, primarily in contact forms, but can also be used in forum software and comment fields.
 *
 * @author  Helge Sverre <email@helgesverre.com>
 *
 * @return object SpamProtection object
 *
 */
class SpamProtection
{

    /**
     * @var bool whether or not to treat Tor Exit nodes as Spam
     */
    protected $allowTorNodes = false;


    /**
     * @var string the base url for the StopForumSpam api, if this ever changes, just change this string
     */
    protected $baseApiUrl = "https://api.stopforumspam.org/api";


    /**
     * @var string the API key for StopForumSpam.org, it's only neccesary if you want to submit spam reports using submitReport()
     */
    protected $apiKey;


    /**
     * @var int the frequency of spam reports that a username/email/ip must
     * have to be considered spam, defaults to THRESHOLD_STRICT, which is 1 spam report
     */
    protected $frequencyThreshold;
    protected $curlEnabled;

    // Convenience constants for various Thresholds
    const THRESHOLD_STRICT = 1;
    const THRESHOLD_HIGH = 3;
    const THRESHOLD_MEDIUM = 5;
    const THRESHOLD_LOW = 10;

    // Convenience constants for allowing or disallowing Tor Exit nodes
    const TOR_ALLOW = true;
    const TOR_DISALLOW = false;


    /**
     * Create a new SpamProtection Object
     * @param int $frequencyThreshold the frequency of spam reports that a username/email/ip must have to be considered spam, defaults to THRESHOLD_STRICT, which is 1 spam report
     * @param bool $allowTorNodes (optional) whether or not to treat Tor Exit nodes as spam
     * @param string $apiKey (optional) Your StopForumSpam.org API key, only neccesary if you plan on using submitReport()
     */
    public function __construct($frequencyThreshold = self::THRESHOLD_STRICT, $allowTorNodes = null, $apiKey = null)
    {
        if (!is_null($frequencyThreshold)) {
            $this->frequencyThreshold = $frequencyThreshold;
        }

        if (!is_null($allowTorNodes)) {
            $this->allowTorNodes = $allowTorNodes;
        }

        if (!is_null($apiKey)) {
            $this->apiKey = $apiKey;
        }

        // Check if curl is enabled
        $this->curlEnabled = function_exists('curl_version');
    }


    /**
     * Builds the URL for the spam check queries
     * @param string $type ip|email|username the type of spam to check $value for
     * @param string $value the ip, email or username to check for spam reports
     * @return string the full url to the api
     */
    protected function buildUrl($type, $value)
    {
        $type = trim(strtolower($type));

        if (!in_array($type, ["ip", "email", "username"])) {
            throw new \InvalidArgumentException("Type of " . $type . " is not supported by the API");
        }

        $url = $this->baseApiUrl . "?";
        $url .= $type . "=";
        $url .= urlencode($value);

        // If Tor nodes are not allowed, add it as a flag.
        if (!$this->allowTorNodes) $url .= "&notorexit";


        return $url . "&f=json";
    }


    /**
     * Sends a simple GET request to a URL and returns the response
     * @param string $url the url to send a GET request to
     * @return mixed
     */
    protected function sendRequest($url)
    {

        $response = null;

        if ($this->curlEnabled) {

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

        } else {
            $response = file_get_contents($url);
        }

        return $response;
    }


    public function check($type, $value)
    {
        $fullApiUrl = $this->buildUrl($type, $value);
        $response = $this->sendRequest($fullApiUrl);

        if ($response) {
            $json = json_decode($response);

            if ($json->success == 1) {
                if ($json->{$type}->appears == 1) {
                    return $json->{$type}->frequency >= $this->frequencyThreshold ? true : false;
                } else {
                    return false;
                }
            }
        } else {
            throw new \Exception("API Check Unsuccessful");
        }
    }


    /**
     * Function used to query the StopForumSpam API for an IP address and return if it is registered as a spamer IP.
     *
     * @param string $ip the IP address to search the API for.
     * @throws \Exception
     * @return bool true if IP is associated with spam, false if not, throws an \Exception on failure
     */
    public function checkIP($ip)
    {
        return $this->check(Types::IP, $ip);
    }


    /**
     * Function used to query the StopForumSpam API for an Email address and return if it is registered as a spam email.
     *
     * @param string $email the Email address to search the API for.
     * @throws \Exception
     * @return bool true means the IP is a spammy email, if false, it's not, throws an \Exception on failure
     */
    public function checkEmail($email)
    {
        return $this->check(Types::EMAIL, $email);
    }


    /**
     * Function used to query the StopForumSpam API for an Email address and return if it is registered as a spam email.
     *
     * @param string $username the Email address to search the API for.
     * @throws \Exception
     * @return bool true means the IP is a spammy email, if false, it's not, throws an \Exception on failure
     */
    public function checkUsername($username)
    {
        return $this->check(Types::USERNAME, $username);
    }


    /**
     * Function used to submit a spam report to StopForumSpam,
     *
     * @author  Helge Sverre <email@helgesverre.com>
     *
     * @param string $username Username of the spammer.
     * @param string $ip the ip of the spammer
     * @param string $evidence evidence of spam, usually you pass it a copy of the original email(with all the headers etc).
     * @param string $email the spammer's email.
     * @return bool returns true if report was submitted, \Exception on failure.
     */
    public function submitReport($username, $ip, $evidence, $email)
    {

        if (!$this->apiKey) {
            throw new \Exception("To submit a spam report you need an API Key");
        }

        $apiUrl = "http://www.stopforumspam.com/add.php"
            . "?username=" . urlencode($username)
            . "&ip_addr=" . urlencode($ip)
            . "&evidence=" . urlencode($evidence)
            . "&email=" . urlencode($email)
            . "&api_key=" . urlencode($this->apiKey);

        $response = $this->sendRequest($apiUrl);

        if (preg_match('/data submitted successfully/', $response)) {
            return true;
        } else {
            throw new \Exception("Submission failed.");
        }
    }

    /**
     * @return bool|null
     */
    public function getAllowTorNodes()
    {
        return $this->allowTorNodes;
    }

    /**
     * @param bool|null $allowTorNodes
     */
    public function setAllowTorNodes($allowTorNodes)
    {
        $this->allowTorNodes = (bool)$allowTorNodes;
    }

    /**
     * @return null
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param null $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return int the frequency threshold
     */
    public function getFrequencyThreshold()
    {
        return $this->frequencyThreshold;
    }

    /**
     * @param int $frequencyThreshold
     */
    public function setFrequencyThreshold($frequencyThreshold)
    {
        $this->frequencyThreshold = (int)$frequencyThreshold;
    }


}
