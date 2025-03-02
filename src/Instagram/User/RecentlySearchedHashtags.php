<?php


namespace Instagram\User;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;

/**
 * Recently Searched Hashtags
 *
 * Get recently searched hashtags for a user.
 *     - Endpoint Format: GET /{ig-user-id}/recently_searched_hashtags?limit={limit}&access_token={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-user/recently_searched_hashtags
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class RecentlySearchedHashtags extends User {
    /**
     * @const Instagram endpoint for the request.
     */
    const ENDPOINT = 'recently_searched_hashtags';

    /**
     * @var max limit per page in the response.
     */
    protected $maxReturnLimit = 30;

    /**
     * Contructor for instantiating a new object.
     *
     * @param array $config for the class.
     * @return void
     */
    public function __construct( $config ) {
        // call parent for setup
        parent::__construct( $config );
    }

    /**
     * Get hashtags a user has recently searched.
     *
     * @param array $params params for the GET request.
     * @return Instagram response.
     */
    public function getSelf( $params = array() ) {
        $getParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->userId . '/' . self::ENDPOINT,
            'params' => $this->getParams( $params )
        );

        // ig get request
        $response = $this->get( $getParams );

        // return response
        return $response;
    }

    /**
     * Get params for the request.
     *
     * @param array $params specific params for the request.
     * @return array of params for the request.
     */
    public function getParams( $params = array() ) {
        if ( $params ) { // specific params have been requested
            return $params;
        } else { // get all params
            // set the limit param to max
            $params[Params::LIMIT] = $this->maxReturnLimit;

            // return our params
            return $params;
        }
    }    
}

?>