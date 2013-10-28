<?php
/*
Zendesk API v2 Wrapper
Zendesk.php
By Mark Huijzers

*/
require_once 'Zendesk/Entity.php';
require_once 'Zendesk/Exceptions.php';
require_once 'Zendesk/Organizations.php';
require_once 'Zendesk/Search.php';
require_once 'Zendesk/Users.php';

class Zendesk
{
    const ZD_URL = "https://{%ACCOUNT%}.zendesk.com/api/v2";

    public $ch;
    private $zdAccount       = '';
    private $zdApiKey        = '';
    private $zdUser          = '';
    private $zdApiUrl        = '';
    public $debug            = true; // enables logging to 'errorLog.log'
    public $local            = false; //If true (when using localhost), disables cURL verification of host and peer

    public static $error_map = array(
        "RecordInvalid" => "Zendesk_RecordInvalid_Error",
    );

    public function __construct ($account=null, $apiKey=null, $user=null, $local=false)
    {
        // validate required connection properties
        if(!$account) $account = getenv('ZENDESK_ACCOUNT');
        if(!$account) throw new Zendesk_Error('You must provide a Zendesk account');
        if(!$apiKey) $apiKey = getenv('ZENDESK_APIKEY');
        if(!$apiKey) throw new Zendesk_Error('You must provide a Zendesk API key');
        if(!$user) $user = getenv('ZENDESK_USER');
        if(!$user) throw new Zendesk_Error('You must provide a Zendesk user');

        // set connection properties
        $this->zdAccount = $account;
        $this->zdApiKey = $apiKey;
        $this->zdUser   = $user;
        $this->local   = $local;
        $this->zdApiUrl = str_replace('{%ACCOUNT%}', strtolower($account), self::ZD_URL);
        $this->zdApiUrl = rtrim($this->zdApiUrl, '/') . '/'; // ensure a single slash on the end

        // Create and initialize cURL client
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'MozillaXYZ/1.0');
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_HEADER, false);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 10 );
        curl_setopt($this->ch, CURLOPT_USERPWD, $this->zdUser."/token:".$this->zdApiKey);

        if ($this->local)
        {
       		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        // Set available API Entities
        $this->organizations    = new Zendesk_Organizations($this);
        $this->search           = new Zendesk_Search($this);
        $this->users            = new Zendesk_Users($this);
    }

    public function __destruct() {
        curl_close($this->ch);
    }

    /**
     * @return array from API
     */
    public function Request($endpoint, $method = "GET", $data = null, $emptyResponse=false)
    {
        $queryString = '';
        $ch = $this->ch;

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // handle data based on method (if set)
        if($data)
        {
            switch($method)
            {
                case "GET":
                    $queryString = self::ArrayToQueryString($data);
                    break;
                case "POST":
                case "PUT":
                    $jsonData = json_encode($data);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                    break;
            }
        }

        $urlComplete = '' . $this->zdApiUrl . $endpoint . '.json' . $queryString;


        // finalize cURL definition
        curl_setopt($ch, CURLOPT_URL, $urlComplete);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);

        $start = microtime(true);
        $this->Log('Call to ' . $urlComplete);

        if($this->debug)
        {
        //    $curl_buffer = fopen('php://memory', 'w+');
        //    curl_setopt($ch, CURLOPT_STDERR, $curl_buffer);
        }

        // Execute cURL request
        $response = curl_exec($ch); //returns json string
        $info = curl_getinfo($ch);

        $time = microtime(true) - $start;

        if($this->debug)
        {
        //    rewind($curl_buffer);
        //    $this->Log(stream_get_contents($curl_buffer));
        //    fclose($curl_buffer);
        }

        $this->Log('Completed in ' . number_format($time * 1000, 2) . 'ms');
        $this->Log('Got response: ' . $response);

        if(curl_error($ch)) {
            throw new Zendesk_HttpError("API call to $urlComplete failed: " . curl_error($ch));
        }
        $result = $response;
        if(!$emptyResponse)
        {
            $result = json_decode($response, true); //returns array
            if($result === null) throw new Zendesk_Error('We were unable to decode the JSON response from the Zendesk API: ' . $response);
        }

        if(floor($info['http_code'] / 100) >= 4) {
            throw $this->CastError($result);
        }

        return $result;
    }

    public function CastError($result) {
        if(!array_key_exists('error', $result) || !array_key_exists('description', $result)) throw new Zendesk_Error('We received an unexpected error: ' . json_encode($result));

        $class = (isset(self::$error_map[$result['error']])) ? self::$error_map[$result['error']] : 'Zendesk_Error';
        return new $class($result['description'].'. Details: '.json_encode($result['details']));
    }

    public function Log($msg) {
        if($this->debug) error_log($msg, 3, 'errorLog.log' );
    }

    /**
     * Changes a array in a querystring ('?key=value&key2-value2,..)
     * @param array $data
     * @return string in querystring format
     */
    private function ArrayToQueryString($data)
    {
        $KeyValueGlue   = '=';
        $paramGlue      = '&';

        if(!is_array($data)) {
            throw new InvalidArgumentException('First parameter must be an array');
        }

        //Change array values in 'key=value'
        array_walk($data, function(&$value, $key, $glue) {
            $value = ''.$key.$glue.urlencode($value);
        }, $KeyValueGlue);

        return '?'.implode($paramGlue, $data);
    }
}