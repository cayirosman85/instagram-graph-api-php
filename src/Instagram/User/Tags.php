<?php


namespace Instagram\User;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;

/**
 * Tags
 *
 * Get media the IG user has been tagged in.
 *     - Endpoint Format: GET /{ig-user-id}/tags?access_toke={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-user/tags
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class Tags extends User {
    /**
     * @const Instagram endpoint for the request.
     */
    const ENDPOINT = 'tags';

    /**
     * @var array $fields a list of all the fields we are requesting to get back.
     */
    protected $fields = array(
        Fields::ID,
        Fields::USERNAME,
        Fields::CAPTION,
        Fields::LIKE_COUNT,
        Fields::COMMENTS_COUNT,
        Fields::TIMESTAMP,
        Fields::MEDIA_PRODUCT_TYPE,
        Fields::MEDIA_TYPE,
        Fields::PERMALINK,
        Fields::MEDIA_URL
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
    }

    /**
     * Get posts the user is tagged in.
     *
     * @param array $params Params for the GET request.
     * @return Instagram Response.
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
            // get media fields for the tagged posts
            $params[Params::FIELDS] = Fields::getDefaultMediaFields() . ',' .
                Fields::CHILDREN . '{' .
                    Fields::getDefaultMediaChildrenFields() .
                '}'
            ;

            // return our params
            return $params;
        }
    }
}

?>