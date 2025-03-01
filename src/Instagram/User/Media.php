<?php


namespace Instagram\User;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;
use Instagram\Request\MediaTypes;

/**
 * Media
 *
 * Handle reading and creating media on the IG user.
 *     - Endpoint Format: GET /{ig-user-id}/media
 *          ?fields={fields}&access_token={access-token}
 *     - Endpoint Format: POST IMAGE /{ig-user-id}/media
 *          ?image_url={image-url}&is_carousel_item={is-carousel-item}&caption={caption}&location_id={location-id}&user_tags={user-tags}&access_token={access-token}
 *     - Endpoint Format: POST VIDEO /{ig-user-id}/media
 *          ?media_type=VIDEO&video_url={video-url}&is_carousel_item={is-carousel-item}&caption={caption}&location_id={location-id}&thumb_offset={thumb-offset}&access_token={access-token}
 *     - Endpoint Format: POST CAROUSEL /{ig-user-id}/media 
 *          ?media_type={media-type}&caption={caption}&location_id={location-id}&children={children}&access_token={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-user/media
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class Media extends User {
    /**
     * @const Instagram endpoint for the request.
     */
    const ENDPOINT = 'media';

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
     * Create a container for posting an image.
     *
     * @param array $params params for the POST request.
     * @return Instagram response.
     */
    public function create( $params ) {
        $postParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->userId . '/' . self::ENDPOINT,
            'params' => $params ? $params : array()
        );

        if ( isset( $params[Params::CHILDREN] ) ) { // carousel container requires more children params
            $postParams['params'][Params::MEDIA_TYPE] = MediaTypes::CAROUSEL;
        } elseif ( isset( $params[Params::VIDEO_URL] ) && !isset( $params[Params::MEDIA_TYPE] ) ) { // video container requires more params and to not overide in case REELS is passed
            $postParams['params'][Params::MEDIA_TYPE] = MediaTypes::VIDEO; 
        } elseif ( isset( $params[Params::VIDEO_URL] ) && isset( $params[Params::MEDIA_TYPE] ) ) { // set url and type to whatever is passed in
            $postParams['params'][Params::MEDIA_TYPE] = $params[Params::MEDIA_TYPE]; 
        } elseif ( isset( $params[Params::IMAGE_URL] ) && isset( $params[Params::MEDIA_TYPE] ) ) { // set url and type to whatever is passed in
            $postParams['params'][Params::MEDIA_TYPE] = MediaTypes::STORIES; 
        }

        // ig get request
        $response = $this->post( $postParams );

        // return response
        return $response;
    }

    /**
     * Get the users media.
     *
     * @param array $params params for the GET request.
     * @return Instagram response.
     */
    public function getSelf( $params = array() ) {
        $getParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->userId . '/' . self::ENDPOINT,
            'params' => $this->getParams( $params ), //$params ? $params : Params::getFieldsParam( $this->fields )
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
            $params[Params::FIELDS] = Fields::getDefaultMediaFieldsString();

            // return our params
            return $params;
        }
    }
}

?>
