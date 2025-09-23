<?php

declare(strict_types=1);

namespace Helge\SpamProtection;

use Helge\SpamProtection\Exception\ApiCheckException;
use Helge\SpamProtection\Exception\ApiKeyMissingException;
use Helge\SpamProtection\Exception\SubmissionFailedException;

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
    protected bool $allowTorNodes = false;


    /**
     * @var string the base url for the StopForumSpam api, if this ever changes, just change this string
     */
    protected string $baseApiUrl = "https://api.stopforumspam.org/api";


    /**
     * @var string|null the API key for StopForumSpam.org, it's only neccesary if you want to submit spam reports using submitReport()
     */
    protected ?string $apiKey = null;


    /**
     * @var int the frequency of spam reports that a username/email/ip must
     * have to be considered spam, defaults to THRESHOLD_STRICT, which is 1 spam report
     */
    protected int $frequencyThreshold = self::THRESHOLD_STRICT;
    protected bool $curlEnabled;

    /**
     * @var int|false the confidence that the the username/email/ip is spam, defaults to false,
     * which means any confidence level
     */
    protected int|false $confidenceThreshold = false;

    // Convenience constants for various Thresholds
    public const THRESHOLD_STRICT = 1;
    public const THRESHOLD_HIGH = 3;
    public const THRESHOLD_MEDIUM = 5;
    public const THRESHOLD_LOW = 10;

    public const CONFIDENCE_STRICT = 99;
    public const CONFIDENCE_HIGH = 80;
    public const CONFIDENCE_MEDIUM = 40;
    public const CONFIDENCE_LOW = 10;

    // Convenience constants for allowing or disallowing Tor Exit nodes
    public const TOR_ALLOW = true;
    public const TOR_DISALLOW = false;


    /**
     * Create a new SpamProtection Object
     * @param int|null $frequencyThreshold the frequency of spam reports that a username/email/ip must have to be considered spam, defaults to THRESHOLD_STRICT, which is 1 spam report
     * @param bool|null $allowTorNodes (optional) whether or not to treat Tor Exit nodes as spam
     * @param string|null $apiKey (optional) Your StopForumSpam.org API key, only neccesary if you plan on using submitReport()
     * @param int|null $confidenceThreshold
     */
    public function __construct(
        ?int $frequencyThreshold = self::THRESHOLD_STRICT,
        ?bool $allowTorNodes = null,
        ?string $apiKey = null,
        ?int $confidenceThreshold = null
    ) {
        if (!is_null($frequencyThreshold)) {
            $this->frequencyThreshold = $frequencyThreshold;
        }

        if (!is_null($allowTorNodes)) {
            $this->allowTorNodes = $allowTorNodes;
        }

        if (!is_null($apiKey)) {
            $this->apiKey = $apiKey;
        }

        if (!is_null($confidenceThreshold)) {
            $this->confidenceThreshold = $confidenceThreshold;
        }

        // Check if curl is enabled
        $this->curlEnabled = function_exists('curl_version');
    }


    /**
     * Builds the URL for the spam check queries
     * @param Types $type ip|email|username the type of spam to check $value for
     * @param string $value the ip, email or username to check for spam reports
     * @return string the full url to the api
     */
    protected function buildUrl(Types $type, string $value): string
    {
        $url = $this->baseApiUrl . "?";
        $url .= $type->value . "=";
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
    protected function sendRequest(string $url): mixed
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


    public function check(Types $type, string $value): bool
    {
        $fullApiUrl = $this->buildUrl($type, $value);
        $response = $this->sendRequest($fullApiUrl);

        if (!$response) {
            throw new ApiCheckException();
        }

        $json = json_decode($response);
        if (!$json || !is_object($json)) {
            throw new ApiCheckException();
        }

        if ((int)$json->success === 1 && (int)$json->{$type->value}->appears === 1) {
            // Frequency Threshold check
            if ($json->{$type->value}->frequency < $this->frequencyThreshold) {
                return false;
            }

            // Run Confidence Threshold check
            if ($this->confidenceThreshold && $json->{$type->value}->confidence < $this->confidenceThreshold) {
                return false;
            }

            return true;
        }

        return false;
    }


    /**
     * Function used to query the StopForumSpam API for an IP address and return if it is registered as a spamer IP.
     *
     * @param string $ip the IP address to search the API for.
     * @throws ApiCheckException
     * @return bool true if IP is associated with spam, false if not
     */
    public function checkIP(string $ip): bool
    {
        return $this->check(Types::IP, $ip);
    }


    /**
     * Function used to query the StopForumSpam API for an Email address and return if it is registered as a spam email.
     *
     * @param string $email the Email address to search the API for.
     * @throws ApiCheckException
     * @return bool true means the email is spam, false if not
     */
    public function checkEmail(string $email): bool
    {
        return $this->check(Types::EMAIL, $email);
    }


    /**
     * Function used to query the StopForumSpam API for a username and return if it is registered as spam.
     *
     * @param string $username the username to search the API for.
     * @throws ApiCheckException
     * @return bool true means the username is spam, false if not
     */
    public function checkUsername(string $username): bool
    {
        return $this->check(Types::USERNAME, $username);
    }


    /**
     * Function used to submit a spam report to StopForumSpam,
     *
     * @author  Helge Sverre <email@helgesverre.com>
     * @param string $username Username of the spammer.
     * @param string $ip the ip of the spammer
     * @param string $evidence evidence of spam, usually you pass it a copy of the original email(with all the headers etc).
     * @param string $email the spammer's email.
     * @throws ApiKeyMissingException
     * @throws SubmissionFailedException
     * @return bool returns true if report was submitted
     */
    public function submitReport(string $username, string $ip, string $evidence, string $email): bool
    {

        if (!$this->apiKey) {
            throw new ApiKeyMissingException();
        }

        $apiUrl = "https://www.stopforumspam.com/add.php"
            . "?username=" . urlencode($username)
            . "&ip_addr=" . urlencode($ip)
            . "&evidence=" . urlencode($evidence)
            . "&email=" . urlencode($email)
            . "&api_key=" . urlencode($this->apiKey);

        $response = $this->sendRequest($apiUrl);

        if (preg_match('/data submitted successfully/', $response)) {
            return true;
        } else {
            throw new SubmissionFailedException();
        }
    }

    public function getAllowTorNodes(): bool
    {
        return $this->allowTorNodes;
    }

    public function setAllowTorNodes(?bool $allowTorNodes): void
    {
        $this->allowTorNodes = (bool)$allowTorNodes;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return int the frequency threshold
     */
    public function getFrequencyThreshold(): int
    {
        return $this->frequencyThreshold;
    }

    public function setFrequencyThreshold(int $frequencyThreshold): void
    {
        $this->frequencyThreshold = $frequencyThreshold;
    }


}
