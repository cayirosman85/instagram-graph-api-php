<?php


namespace Instagram\Hashtag;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;

/**
 * Top Media
 *
 * Get top media for a hashtag.
 *     - Endpoint Format: GET /{ig-hashtag-id}/top_media?user_id={user-id}&fields={fields}&access_token={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-hashtag/top-media#returnable-fields
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class TopMedia extends Hashtag {
    /**
     * @const Instagram endpoint for the request.
     */
    const ENDPOINT = 'top_media';

    /**
     * @var integer $userId Instagram user id making the api request.
     */
    protected $userId;

    /**
     * Contructor for instantiating a new object.
     *
     * @param array $config for the class.
     * @return void
     */
    public function __construct( $config ) {
        // call parent for setup
        parent::__construct( $config );

        // store the user id
        $this->userId = $config['user_id'];
    }

    /**
     * Get info on a hashtag.
     *
     * @param array $params Params for the GET request.
     * @return Instagram Response.
     */
    public function getSelf( $params = array() ) {
        $getParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->hashtagId . '/' . self::ENDPOINT,
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
     * @param array $params Specific params for the request.
     * @return array of params for the request.
     */
    public function getParams( $params = array() ) {
        if ( $params ) { // specific params have been requested
            return $params;
        } else { // get all params
            // get field params
            $params[Params::FIELDS] = Fields::getDefaultMediaFields() . ',' .
                Fields::CHILDREN . '{' .
                    Fields::getDefaultMediaChildrenFields() .
                '}'
            ;

            // add on the since query param farthest back it can be set is 24 hours
            $params[Params::USER_ID] = $this->userId;

            // return our params
            return $params;
        }
    }
}

?>