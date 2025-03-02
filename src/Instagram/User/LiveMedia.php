<?php


namespace Instagram\User;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;

/**
 * Live Media
 *
 * Get live media on the IG user.
 *     - Endpoint Format: GET /{ig-user-id}/live_media?fields={fields}&since={since}&until={until}&access_token={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-user/live_media
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class LiveMedia extends User {
    /**
     * @const Instagram endpoint for the request.
     */
    const ENDPOINT = 'live_media';

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
     * Get live media for a user.
     *
     * @param array $params params for the GET request.
     * @return Instagram response.
     */
    public function getSelf( $params = array() ) {
        $getParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->userId . '/' . self::ENDPOINT,
            'params' => $params ? $params : Params::getFieldsParam( Fields::getDefaultMediaFields( false ) )
        );

        // ig get request
        $response = $this->get( $getParams );

        // return response
        return $response;
    }
}

?>