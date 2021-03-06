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
            'apiKey' => $this->apiKey
        ]
        ];

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
}