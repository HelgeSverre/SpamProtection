<?php

namespace Helge\SpamProtection;

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


    protected $allowTorNodes = false;

    protected $baseApiUrl = "http://api.stopforumspam.org/api";

    protected $apiKey;

    protected $frequencyThreshold;

    protected $curlEnabled;

    const THRESHOLD_STRICT = 1;
    const THRESHOLD_HIGH = 3;
    const THRESHOLD_MEDIUM = 5;
    const THRESHOLD_LOW = 10;

    const TOR_ALLOW = true;
    const TOR_DISALLOW = false;


    public function __construct($frequencyThreshold = null, $allowTorNodes = null, $apiKey = null)
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
                return $json->{$type}->frequency >= $this->frequencyThreshold ? true : false;
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