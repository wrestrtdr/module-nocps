<?php
/**
 * NOC-PS API
 *
 * Author: Floris Bos (Maxnet)
 * bos@je-eigen-domein.nl
 *
 * Wrapper class that uses the JsonRpc\Client class to do the dirty work! :-)
 * JSON-RPC automatically detects which methods are supported by the server, so you will not find them listed here
 * Read the documentation instead
 */

/* NOC-PS server hostname. Do not enter the IP or SSL certificate validation will fail */
define('NOCPS_DEFAULT_SERVER', '');
/* Username */
define('NOCPS_DEFAULT_USERNAME', '');
/* Password */
define('NOCPS_DEFAULT_PASSWORD', '');
/* Validate SSL certificate. Set to true for production environments */
define('NOCPS_DEFAULT_SSLVERIFY', false);

/* Check for necessary extensions */
if ( !extension_loaded('json') )
    die("Error! JSON extension is missing!");
if ( !extension_loaded('curl') )
    die("Error! cURL extension is missing!");
$curlinfo = curl_version();
if ( !$curlinfo['features'] & constant('CURL_VERSION_SSL') )
    die("Error! cURL does not have SSL support! Cowardly refusing insecure communication!");


class nocps_api extends nocps_JsonRPC_Client
{
    /**
     * Constructor
     *
     * Connect to the NOC-PS server
     * @param string $server     Hostname of server (will use default if empty)
     * @param string $user       Username
     * @param string $password   Password
     * @param bool   $ssl_verify Whether or not to verify the SSL certificate
     * @param string $loguser    Extra info for logging. E.g. username performing the action
     */
    function __construct($server = NOCPS_DEFAULT_SERVER, $user = NOCPS_DEFAULT_USERNAME,
                         $password = NOCPS_DEFAULT_PASSWORD, $ssl_verify = NOCPS_DEFAULT_SSLVERIFY,
                         $loguser = "")
    {
        if (!$server)
            die("Please set the server, username and password in ".__FILE__);

        $headers = array();
        /* If the API is used in a website, include IP-address of client as header for logging */
        if (!empty($_SERVER["REMOTE_ADDR"]))
        {
            if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
                $headers["X-Forwarded-For"] = $_SERVER["HTTP_X_FORWARDED_FOR"].", ".$_SERVER['REMOTE_ADDR'];
            else
                $headers["X-Forwarded-For"] = $_SERVER['REMOTE_ADDR'];
        }
        if ($loguser)
        {
            $headers["X-Forwarded-For-User"] = $loguser;
        }

        parent::__construct("https://$server/jsonrpc.php", 30, $headers);
        $this->authentication($user, $password);
        $this->named_arguments = false;
        $this->ssl_verify_peer = $ssl_verify;
    }
}

/**
 * JsonRPC client class
 *
 * @package JsonRPC
 * @author Frederic Guillot
 * @license Unlicense http://unlicense.org/
 */
class nocps_JsonRPC_Client
{
    /**
     * URL of the server
     *
     * @access private
     * @var string
     */
    private $url;

    /**
     * If the only argument passed to a function is an array
     * assume it contains named arguments
     *
     * @access public
     * @var boolean
     */
    public $named_arguments = true;

    /**
     * HTTP client timeout
     *
     * @access private
     * @var integer
     */
    private $timeout;

    /**
     * Username for authentication
     *
     * @access private
     * @var string
     */
    private $username;

    /**
     * Password for authentication
     *
     * @access private
     * @var string
     */
    private $password;

    /**
     * True for a batch request
     *
     * @access public
     * @var boolean
     */
    public $is_batch = false;

    /**
     * Batch payload
     *
     * @access public
     * @var array
     */
    public $batch = array();

    /**
     * Enable debug output to the php error log
     *
     * @access public
     * @var boolean
     */
    public $debug = false;

    /**
     * Default HTTP headers to send to the server
     *
     * @access private
     * @var array
     */
    private $headers = array(
        'Content-Type: application/json',
        'Accept: application/json'
    );
    /**
     * SSL certificates verification
     * @access public
     * @var boolean
     */
    public $ssl_verify_peer = true;

    /**
     * cURL handle
     *
     * @access private
     */
    private $ch;

    /**
     * Constructor
     *
     * @access public
     * @param  string    $url         Server URL
     * @param  integer   $timeout     Server URL
     * @param  array     $headers     Custom HTTP headers
     */
    public function __construct($url, $timeout = 5, $headers = array())
    {
        $this->url = $url;
        $this->timeout = $timeout;
        $this->headers = array_merge($this->headers, $headers);
        $this->ch = curl_init();
    }

    /**
     * Destructor
     *
     * @access public
     */
    public function __destruct()
    {
        curl_close($this->ch);
    }

    /**
     * Automatic mapping of procedures
     *
     * @access public
     * @param  string   $method   Procedure name
     * @param  array    $params   Procedure arguments
     * @return mixed
     */
    public function __call($method, array $params)
    {
        // Allow to pass an array and use named arguments
        if ($this->named_arguments && count($params) === 1 && is_array($params[0])) {
            $params = $params[0];
        }

        return $this->execute($method, $params);
    }

    /**
     * Set authentication parameters
     *
     * @access public
     * @param  string   $username   Username
     * @param  string   $password   Password
     */
    public function authentication($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Start a batch request
     *
     * @access public
     * @return Client
     */
    public function batch()
    {
        $this->is_batch = true;
        $this->batch = array();

        return $this;
    }

    /**
     * Send a batch request
     *
     * @access public
     * @return array
     */
    public function send()
    {
        $this->is_batch = false;

        return $this->parseResponse(
            $this->doRequest($this->batch)
        );
    }

    /**
     * Execute a procedure
     *
     * @access public
     * @param  string   $procedure   Procedure name
     * @param  array    $params      Procedure arguments
     * @return mixed
     */
    public function execute($procedure, array $params = array())
    {
        if ($this->is_batch) {
            $this->batch[] = $this->prepareRequest($procedure, $params);
            return $this;
        }

        return $this->parseResponse(
            $this->doRequest($this->prepareRequest($procedure, $params))
        );
    }

    /**
     * Prepare the payload
     *
     * @access public
     * @param  string   $procedure   Procedure name
     * @param  array    $params      Procedure arguments
     * @return array
     */
    public function prepareRequest($procedure, array $params = array())
    {
        $payload = array(
            'jsonrpc' => '2.0',
            'method' => $procedure,
            'id' => mt_rand()
        );

        if (! empty($params)) {
            $payload['params'] = $params;
        }

        return $payload;
    }

    /**
     * Parse the response and return the procedure result
     *
     * @access public
     * @param  array     $payload
     * @return mixed
     */
    public function parseResponse(array $payload)
    {
        if ($this->isBatchResponse($payload)) {

            $results = array();

            foreach ($payload as $response) {
                $results[] = $this->getResult($response);
            }

            return $results;
        }

        return $this->getResult($payload);
    }

    /**
     * Return true if we have a batch response
     *
     * @access public
     * @param  array    $payload
     * @return boolean
     */
    private function isBatchResponse(array $payload)
    {
        return array_keys($payload) === range(0, count($payload) - 1);
    }

    /**
     * Get a RPC call result
     *
     * @access public
     * @param  array    $payload
     * @return mixed
     */
    public function getResult(array $payload)
    {
        if (isset($payload['error']['code'])) {
            $this->handleRpcErrors($payload['error']);
        }

        return isset($payload['result']) ? $payload['result'] : null;
    }

    /**
     * Throw an exception according the RPC error
     *
     * @access public
     * @param  integer    $code
     */
    public function handleRpcErrors($error)
    {
        switch ($error['code']) {
            case -32601:
                throw new BadFunctionCallException('Procedure not found: '. $error['message']);
            case -32602:
                throw new InvalidArgumentException('Invalid arguments: '. $error['message']);
            default:
                throw new RuntimeException('Invalid request/response: '. $error['message'], $error['code']);
        }
    }

    /**
     * Do the HTTP request
     *
     * @access public
     * @param  string   $payload   Data to send
     */
    public function doRequest($payload)
    {
        curl_setopt_array($this->ch, array(
            CURLOPT_URL => $this->url,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => 'JSON-RPC PHP Client',
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYPEER => $this->ssl_verify_peer,
            CURLOPT_POSTFIELDS => json_encode($payload)
        ));

        if ($this->username && $this->password) {
            curl_setopt($this->ch, CURLOPT_USERPWD, $this->username.':'.$this->password);
        }

        $http_body = curl_exec($this->ch);
        $http_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

        if ($http_code === 401 || $http_code === 403) {
            throw new RuntimeException('Access denied');
        }
        if ($http_body === false) {
            throw new RuntimeException(curl_error($this->ch));
        }

        $response = json_decode($http_body, true);

        if ($this->debug) {
            error_log('==> Request: '.PHP_EOL.json_encode($payload, JSON_PRETTY_PRINT));
            error_log('==> Response: '.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT));
        }

        return is_array($response) ? $response : array();
    }
}
