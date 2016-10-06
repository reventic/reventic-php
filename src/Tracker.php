<?php namespace Reventic\ReventicSDK;

use GuzzleHttp\Client;

class Tracker
{

    private $apiKey = null;

    private $reventicUrl = 'https://api.reventic.com';
    private $demoReventicUrl = 'https://demo.reventic.com/';

    private $userId;
    private $sessionId;
    private $userIp;

    private $userIdCookieName = 'reventic';
    private $sessionIdCookieName = 'rev-session';

    private $client;

    /**
     * On init setup new http client and set user and session IDs
     * @param null $apiKey
     * @param bool $demo
     */
    public function __construct($apiKey = null, $demo = false)
    {
        if ($apiKey) {
            $this->apiKey = $apiKey;
        }

        if ($demo) {
            $this->reventicUrl = $this->demoReventicUrl;
        }

        $this->client = new Client([
            'base_uri' => $this->reventicUrl,
            'timeout' => 2.0,
        ]);

        $this->setUserId();
        $this->setSessionId();
        $this->setIpAddress();
    }

    /**
     * Set API key
     * @param $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Track events
     * @param $event
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function track($event, $data = [])
    {
        return $this->execute(array_merge(['name' => $event], $data));
    }

    /**
     * Update user data
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function updateUser($data = [])
    {
        return $this->execute($data, 'user');
    }

    /**
     * Execute API request
     * @param $data
     * @param string $type
     * @return mixed
     * @throws \Exception
     */
    public function execute($data, $type = 'event')
    {
        if (!$this->apiKey) {
            throw new \Exception('No API Key!');
        }

        $payload = $this->setPayload($data);

        $response = $this->client->request('POST', $type, $payload);

        $response = $this->parseResponse($response);

        return $response;
    }

    /**
     * Set payload data to be sent to API
     * @param $data
     * @return array
     */
    private function setPayload($data)
    {
        $payload = ['json' => [
            'properties' => $data,
            'apiKey' => $this->apiKey,
            'revIp' => $this->userIp
        ]];

        if ($this->userId) {
            $payload['json']['userId'] = $this->userId;
        }

        if ($this->sessionId) {
            $payload['json']['sessionId'] = $this->sessionId;
        }

        $payload['headers'] = [
            'apiKey' => $this->apiKey
        ];

        return $payload;
    }

    /**
     * Set userId from cookie
     * @return mixed
     */
    private function setUserId()
    {
        $this->userId = isset($_COOKIE[$this->userIdCookieName]) ? $_COOKIE[$this->userIdCookieName] : null;

        return $this->userId;
    }

    /**
     * Update reventic cookie, set user ID
     * @param $userId
     * @return mixed
     */
    private function updateUserId($userId)
    {
        setcookie($this->userIdCookieName, $userId, time() + (10 * 365 * 24 * 60 * 60));

        $this->userId = $userId;

        return $this->userId;
    }

    /**
     * Set session ID from cookie
     * @return mixed
     */
    private function setSessionId()
    {
        $this->sessionId = isset($_COOKIE[$this->sessionIdCookieName]) ? $_COOKIE[$this->sessionIdCookieName] : null;

        return $this->sessionId;
    }

    /**
     * Update reventic cooke, set session ID
     * @param $sessionId
     * @return mixed
     */
    private function updateSessionId($sessionId)
    {
        setcookie($this->sessionIdCookieName, $sessionId, 0);

        $this->sessionId = $sessionId;

        return $this->sessionId;
    }

    /**
     * Parse json encoded data returned from reventic API
     * @param $response
     * @return mixed
     */
    private function parseResponse($response)
    {
        $data = json_decode($response->getBody()->getContents());

        if (isset($data->user->id)) {
            $this->updateUserId($data->user->id);
        }

        if (isset($data->session->id)) {
            $this->updateSessionId($data->session->id);
        }

        return $data;
    }

    /**
     * Set user IP address
     * @return bool
     */
    private function setIpAddress() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    // trim for safety measures
                    $ip = trim($ip);
                    // attempt to validate IP
                    if ($this->validateIp($ip)) {
                        $this->userIp = $ip;
                    }
                }
            }
        }

        $this->userIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;

        return $this->userIp;
    }

    /**
     * Ensures an ip address is both a valid IP and does not fall within
     * a private network range.
     * @param $ip
     * @return bool
     */
    private function validateIp($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        return true;
    }
}