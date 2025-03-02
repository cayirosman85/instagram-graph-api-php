<?php


namespace Instagram\User;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;

/**
 * Mentioned Media
 *
 * Get info on the media a user is mentioned in.
 *     - Endpoint Format: GET /{ig-user-id}?fields=mentioned_media.media_id({media-id}){{fields}}&access_token={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-user/mentioned_media
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class MentionedMedia extends User {
    /**
     * @var integer $mediaId Instagram media id making the api request.
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
     * Get media the user is mentioned in.
     *
     * @param array $params Params for the GET request.
     * @return Instagram Response.
     */
    public function getSelf( $params = array() ) {
        $getParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->userId . '/',
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
            // get field params
            $params[Params::FIELDS] = Fields::MENTIONED_MEDIA . '.' . Fields::MEDIA_ID . '(' . $this->mediaId . '){' .
                Fields::getDefaultMediaFieldsString() .
            '}';

            // return our params
            return $params;
        }
    }
}

?>