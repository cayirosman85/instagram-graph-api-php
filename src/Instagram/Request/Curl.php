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
    public function send( $request ) {
        $options = array( // curl options for the connection
            CURLOPT_URL => $request->getUrl(),
            CURLOPT_RETURNTRANSFER => true, // Return response as string
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_CAINFO => __DIR__ . '/certs/cacert.pem',
        );

        if ( $request->getMethod() == Request::METHOD_POST ) { // need to add on post fields
            $options[CURLOPT_POSTFIELDS] = $request->getUrlBody();
        }

        // initialize curl
        $this->curl = curl_init();

        // set the options
        curl_setopt_array( $this->curl, $options );

        // send the request
        $this->rawResponse = curl_exec( $this->curl );

        // close curl connection
        curl_close( $this->curl );

        // return nice json decoded response
        return json_decode( $this->rawResponse, true );
    }
}

?>