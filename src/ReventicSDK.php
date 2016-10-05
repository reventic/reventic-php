<?php namespace Reventic\ReventicSDK;

use GuzzleHttp\Client;

class Tracker
{

    private $apiKey = null;
    private $reventicUrl = 'https://demo.reventic.com/';
    private $userId;
    private $sessionId;

    private $userIdCookieName = 'reventic';
    private $sessionIdCookieName = 'rev-session';

    private $client;

    /**
     * On init setup new http client and set user and session IDs
     * @param null $apiKey
     */
    public function __construct($apiKey = null)
    {
        if ($apiKey) {
            $this->apiKey = $apiKey;
        }

        $this->client = new Client([
            'base_uri' => $this->reventicUrl,
            'timeout' => 2.0,
        ]);

        $this->setUserId();
        $this->setSessionId();
    }

    /**
     * Set API key
     * @param $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function track($event, $data = [])
    {
        if (!$this->apiKey) {
            throw new \Exception('No API Key!');
        }

        $payload = [
            'properties' => array_merge(['name' => $event], $data),
            'apiKey' => $this->apiKey,
            'userId' => $this->userId,
            'sessionId' => $this->sessionId
        ];

        $response = $this->client->request('POST', 'event', ['json' => $payload]);

        var_dump($response);
    }

    public function updateUser($data = [])
    {
        if (!$this->apiKey) {
            throw new \Exception('No API Key!');
        }

        $payload = [
            'properties' => $data,
            'apiKey' => $this->apiKey,
            'userId' => $this->userId,
            'sessionId' => $this->sessionId
        ];

        $response = $this->client->request('POST', 'user', ['json' => $payload]);

        var_dump($response);
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
}