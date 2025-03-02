<?php


namespace Instagram\User;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;

/**
 * User
 *
 * Get the IG Users info.
 *     - Endpoint Format: GET /{ig-user-id}?fields={fields}&access_token={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-user
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class User extends Instagram {
    /**
     * @const Instagram endpoint for the request.
     */
    const ENDPOINT = 'me/accounts';

    /**
     * @var string $userId Instagram user id.
     */
    protected $userId;

    /**
     * @var array $fields a list of all the fields we are requesting to get back.
     */
    protected $fields = array(
        Fields::BIOGRAPHY,
        Fields::ID,
        Fields::IG_ID,
        Fields::FOLLOWERS_COUNT,
        Fields::FOLLOWS_COUNT,
        Fields::MEDIA_COUNT,
        Fields::NAME,
        Fields::PROFILE_PICTURE_URL,
        Fields::USERNAME,
        Fields::WEBSITE
    );

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
        $this->userId = isset( $config['user_id'] ) ? $config['user_id'] : '';
    }

    /**
     * Get the users info.
     *
     * @param array $params params for the GET request.
     * @return Instagram response.
     */
    public function getSelf( $params = array() ) {
        $getParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->userId,
            'params' => $params ? $params : Params::getFieldsParam( $this->fields )
        );

        // ig get request
        $response = $this->get( $getParams );

        // return response
        return $response;
    }

    /**
     * Get the users facebook pages.
     *
     * @param array $params params for the GET request.
     * @return Instagram response.
     */
    public function getUserPages( $params = array() ) {
        $getParams = array( // parameters for our endpoint
            'endpoint' => '/' . self::ENDPOINT,
            'params' => $params
        );

        // ig get request
        $response = $this->get( $getParams );

        // calculate the next link for paging
        $this->calcLinkFromCursor( Params::AFTER, $response, $getParams['endpoint'], $params );

        // calcuate the before link
        $this->calcLinkFromCursor( Params::BEFORE, $response, $getParams['endpoint'], $params );

        // set prev and next links
        $this->setPrevNextLinks( $response );

        // return response
        return $response;
    }
}

?>