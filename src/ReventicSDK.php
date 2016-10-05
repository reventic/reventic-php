<?php namespace Reventic\ReventicSDK;

class ReventicSDK {

    private $apiKey = null;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

}