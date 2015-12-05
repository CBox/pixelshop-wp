<?php

require_once 'api/Export.php';

class API {
    
    public $apikey;
    public $ch;
    public $root = 'https://pixelshop.io/api/';
    public $debug = false;

    public function __construct($apikey=null) {
        $this->apikey = $apikey;

        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'PixelShop-PHP/1.0.0');
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_HEADER, false);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 600);

        $this->root = rtrim($this->root, '/') . '/';

        $this->export = new API_Export($this);
    }

    public function __destruct() {
        curl_close($this->ch);
    }

    public function call($url, $params) {
        $params['key'] = $this->apikey;

        $params = json_encode($params);

        $ch = $this->ch;

        curl_setopt($ch, CURLOPT_URL, $this->root . $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);

        $start = microtime(true);

        $this->log('Call to ' . $this->root . $url . ' ' . $params);

        $response_body = curl_exec($ch);
        $info = curl_getinfo($ch);

        $time = microtime(true) - $start;

        $this->log('Completed in ' . number_format($time * 1000, 2) . 'ms');
        $this->log('Got response: ' . $response_body);

        if(curl_error($ch)) {
            throw new Exception("API call to $url failed: " . curl_error($ch));
        }

        $result = json_decode($response_body, true);

        if( isset($result->error) ) throw new Exception('API Key not valid, Pixelshop API Error: ' . $result->error);

        if($result === null) throw new Exception('We were unable to decode the JSON response from the Pixelshop API: ' . $response_body);
        
        if(floor($info['http_code'] / 100) >= 4) {
            throw $this->castError($result);
        }

        return $result;
    }

    public function castError($result) {
        if($result['status'] !== 'error' || !$result['name']) throw new Exception('We received an unexpected error: ' . json_encode($result));

        $class = (isset(self::$error_map[$result['name']])) ? self::$error_map[$result['name']] : 'Exception';
        return new $class($result['message'], $result['code']);
    }

    public function log($msg) {
        if($this->debug) error_log($msg);
    }
}