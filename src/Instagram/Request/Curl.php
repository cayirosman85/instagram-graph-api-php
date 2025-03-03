<?php


namespace Instagram\Request;

// other classes we need to use
use Instagram\Request\Request;

/**
 * Curl
 *
 * Handle curl functionality for requests.
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class Curl {
	/**
     * @var object $curl
     */
    protected $curl;

    /**
     * @var int The curl client error code.
     */
    protected $curlErrorCode = 0;
    
	/**
     * @var string $rawResponse The raw response from the server.
     */
    protected $rawResponse;

    /**
     * Perform a curl call.
     * 
     * @param Request $request
     * @return array The curl response.
     */
    public function send($request) {
        $options = array(
            CURLOPT_URL => $request->getUrl(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_CAINFO => __DIR__ . '/certs/cacert.pem',
            CURLOPT_TIMEOUT => 60, // Timeout after 10 seconds
            CURLOPT_CONNECTTIMEOUT => 5, // Connection timeout
        );
    
        if ($request->getMethod() == Request::METHOD_POST) {
            $options[CURLOPT_POSTFIELDS] = $request->getUrlBody();
        }
    
        $this->curl = curl_init();
        curl_setopt_array($this->curl, $options);
        $this->rawResponse = curl_exec($this->curl);
    
        // Check for Curl errors
        if ($this->rawResponse === false) {
            $this->curlErrorCode = curl_errno($this->curl);
            error_log("Curl error: " . curl_error($this->curl));
        }
    
        curl_close($this->curl);
        return json_decode($this->rawResponse, true);
    }
}

?>