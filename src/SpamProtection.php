<?php

declare(strict_types=1);

namespace Helge\SpamProtection;

use Helge\SpamProtection\Exception\ApiCheckException;
use Helge\SpamProtection\Exception\ApiKeyMissingException;
use Helge\SpamProtection\Exception\SubmissionFailedException;
use JsonException;
use RuntimeException;

/**
 * Class used for spam prevention, primarily in contact forms, but can also be used in forum software and comment fields.
 *
 * @author  Helge Sverre <email@helgesverre.com>
 *
 * @return object SpamProtection object
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
    protected string $baseApiUrl = 'https://api.stopforumspam.org/api';

    /**
     * @var string|null the API key for StopForumSpam.org, it's only neccesary if you want to submit spam reports using submitReport()
     */
    protected ?string $apiKey = null;

    /**
     * @var int the frequency of spam reports that a username/email/ip must
     *          have to be considered spam, defaults to THRESHOLD_STRICT, which is 1 spam report
     */
    protected int $frequencyThreshold = self::THRESHOLD_STRICT;
    protected bool $curlEnabled;

    /**
     * @var int|false the confidence that the the username/email/ip is spam, defaults to false,
     *                which means any confidence level
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
     *
     * @param int|null    $frequencyThreshold  the frequency of spam reports that a username/email/ip must have to be considered spam, defaults to THRESHOLD_STRICT, which is 1 spam report
     * @param bool|null   $allowTorNodes       (optional) whether or not to treat Tor Exit nodes as spam
     * @param string|null $apiKey              (optional) Your StopForumSpam.org API key, only neccesary if you plan on using submitReport()
     * @param int|null    $confidenceThreshold
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
     *
     * @param Types  $type  ip|email|username the type of spam to check $value for
     * @param string $value the ip, email or username to check for spam reports
     *
     * @return string the full url to the api
     */
    protected function buildUrl(Types $type, string $value): string
    {
        $url = $this->baseApiUrl . '?';
        $url .= $type->value . '=';
        $url .= urlencode($value);

        // If Tor nodes are not allowed, add it as a flag.
        if (!$this->allowTorNodes) {
            $url .= '&notorexit';
        }

        return $url . '&f=json';
    }

    /**
     * Sends a simple GET request to a URL and returns the response
     *
     * @param string $url the url to send a GET request to
     *
     * @return mixed
     */
    protected function sendRequest(string $url): mixed
    {
        if ($this->curlEnabled) {
            $ch = curl_init($url);
            if ($ch === false) {
                throw new RuntimeException('Could not initialize the HTTP request');
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $response = curl_exec($ch);
            $error = curl_errno($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            unset($ch);

            if ($response === false) {
                // cURL's raw error may contain the request URL and API key.
                throw new RuntimeException('HTTP request failed: ' . curl_strerror($error), $error);
            }
        } else {
            $context = stream_context_create(['http' => [
                'timeout' => 15,
                'ignore_errors' => true,
                'follow_location' => 0,
            ]]);
            $stream = @fopen($url, 'rb', false, $context);
            if ($stream === false) {
                throw new RuntimeException('Could not connect to the API');
            }

            try {
                $response = @stream_get_contents($stream);
                $metadata = stream_get_meta_data($stream);
            } finally {
                fclose($stream);
            }

            if ($response === false) {
                throw new RuntimeException('Could not read the API response');
            }

            $status = 0;
            foreach ($metadata['wrapper_data'] ?? [] as $header) {
                if (preg_match('/^HTTP\/\S+ (\d{3})/', $header, $matches)) {
                    $status = (int) $matches[1];
                }
            }
        }

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('API returned HTTP ' . $status, $status);
        }

        return $response;
    }

    public function check(Types $type, string $value): bool
    {
        $fullApiUrl = $this->buildUrl($type, $value);
        try {
            $response = $this->sendRequest($fullApiUrl);
        } catch (RuntimeException $e) {
            throw new ApiCheckException($e->getCode(), $e, $e->getMessage());
        }

        if (!is_string($response) || $response === '') {
            throw new ApiCheckException(detail: 'Empty API response');
        }

        try {
            $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ApiCheckException(previous: $e, detail: 'Invalid JSON response');
        }

        if (!is_array($json) || !array_key_exists('success', $json)) {
            throw new ApiCheckException(detail: 'Missing API success status');
        }

        if (!in_array($json['success'], [1, '1', true], true)) {
            throw new ApiCheckException(detail: $this->apiError($json['error'] ?? null));
        }

        $typeData = $json[$type->value] ?? null;
        if (!is_array($typeData) || !array_key_exists('appears', $typeData) ||
            !in_array($typeData['appears'], [0, '0', false, 1, '1', true], true)) {
            throw new ApiCheckException(detail: 'Missing or invalid ' . $type->value . ' result');
        }

        if (in_array($typeData['appears'], [0, '0', false], true)) {
            return false;
        }

        if (!isset($typeData['frequency']) || !is_numeric($typeData['frequency']) || $typeData['frequency'] < 0) {
            throw new ApiCheckException(detail: 'Missing or invalid report frequency');
        }

        if ($typeData['frequency'] < $this->frequencyThreshold) {
            return false;
        }

        if ($this->confidenceThreshold) {
            if (!isset($typeData['confidence']) || !is_numeric($typeData['confidence']) ||
                $typeData['confidence'] < 0 || $typeData['confidence'] > 100) {
                throw new ApiCheckException(detail: 'Missing or invalid confidence score');
            }

            if ($typeData['confidence'] < $this->confidenceThreshold) {
                return false;
            }
        }

        return true;
    }

    /**
     * Function used to query the StopForumSpam API for an IP address and return if it is registered as a spamer IP.
     *
     * @param string $ip the IP address to search the API for
     *
     * @throws ApiCheckException
     *
     * @return bool true if IP is associated with spam, false if not
     */
    public function checkIP(string $ip): bool
    {
        return $this->check(Types::IP, $ip);
    }

    /**
     * Function used to query the StopForumSpam API for an Email address and return if it is registered as a spam email.
     *
     * @param string $email the Email address to search the API for
     *
     * @throws ApiCheckException
     *
     * @return bool true means the email is spam, false if not
     */
    public function checkEmail(string $email): bool
    {
        return $this->check(Types::EMAIL, $email);
    }

    /**
     * Function used to query the StopForumSpam API for a username and return if it is registered as spam.
     *
     * @param string $username the username to search the API for
     *
     * @throws ApiCheckException
     *
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
     *
     * @param string $username username of the spammer
     * @param string $ip       the ip of the spammer
     * @param string $evidence evidence of spam, usually you pass it a copy of the original email(with all the headers etc)
     * @param string $email    the spammer's email
     *
     * @throws ApiKeyMissingException
     * @throws SubmissionFailedException
     *
     * @return bool returns true if report was submitted
     */
    public function submitReport(string $username, string $ip, string $evidence, string $email): bool
    {

        if (!$this->apiKey) {
            throw new ApiKeyMissingException();
        }

        $apiUrl = 'https://www.stopforumspam.com/add.php'
            . '?username=' . urlencode($username)
            . '&ip_addr=' . urlencode($ip)
            . '&evidence=' . urlencode($evidence)
            . '&email=' . urlencode($email)
            . '&api_key=' . urlencode($this->apiKey)
            . '&f=json';

        try {
            $response = $this->sendRequest($apiUrl);
        } catch (RuntimeException $e) {
            throw new SubmissionFailedException($e->getCode(), $e, $e->getMessage());
        }

        if (!is_string($response) || $response === '') {
            throw new SubmissionFailedException(detail: 'Empty API response');
        }

        $json = json_decode($response, true);
        if (is_array($json) && array_key_exists('success', $json)) {
            if (in_array($json['success'], [1, '1', true], true)) {
                return true;
            }

            throw new SubmissionFailedException(detail: $this->apiError($json['error'] ?? null));
        }

        // Retain compatibility with the API's original HTML success response.
        if (strtolower(trim(strip_tags($response))) === 'data submitted successfully') {
            return true;
        }

        throw new SubmissionFailedException(detail: 'Unexpected API response');
    }

    private function apiError(mixed $error): string
    {
        if (!is_string($error) || trim($error) === '') {
            return 'API rejected the request';
        }

        if ($this->apiKey !== null && $this->apiKey !== '') {
            $error = str_replace(
                [$this->apiKey, urlencode($this->apiKey), rawurlencode($this->apiKey)],
                '[redacted]',
                $error
            );
        }

        return substr(trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', strip_tags($error)) ?? ''), 0, 250);
    }

    public function getAllowTorNodes(): bool
    {
        return $this->allowTorNodes;
    }

    public function setAllowTorNodes(?bool $allowTorNodes): void
    {
        $this->allowTorNodes = (bool) $allowTorNodes;
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
