<?php


namespace Instagram\Media;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;

/**
 * Media
 *
 * Get info on a specific media.
 *     - Endpoint Format: GET /{ig-media-id}?access_token={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-media
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class Media extends Instagram {
    /**
     * @var integer $mediaId Instagram id of the media to get info on.
     */
    protected $mediaId;

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
        $this->mediaId = $config['media_id'];
    }

    /**
     * Get info on a hashtag.
     *
     * @param array $params Params for the GET request.
     * @return Instagram Response.
     */
    public function getSelf( $params = array() ) {
        $getParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->mediaId,
            'params' => $this->getParams( $params )
        );

        // ig get request
        $response = $this->get( $getParams );

        // return response
        return $response;
    }

    /**
     * Set comments endabled for a media post.
     *
     * @param boolean $commentsEnabled enable or diable comments with true|false.
     * @return Instagram Response.
     */
    public function setCommentsEnabled( $commentsEnabled ) {
        $postParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->mediaId,
            'params' => array(
                Params::COMMENT_ENABLED => $commentsEnabled
            )
        );

        // ig get request
        $response = $this->post( $postParams );

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
            // get field params
            $params[Params::FIELDS] = Fields::getDefaultMediaFieldsString();

            // return our params
            return $params;
        }
    }
}

?>