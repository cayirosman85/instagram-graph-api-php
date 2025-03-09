<?php


namespace Instagram\User;

// other classes we need to use
use Instagram\Instagram;

/**
 * Stories
 *
 * Get stories on the IG user.
 *     - Endpoint Format: GET /{ig-user-id}/stories?access_token={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-user/stories
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class Stories extends User {
    /**
     * @const Instagram endpoint for the request.
     */
    const ENDPOINT = 'stories';

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
     * Get the users stories.
     *
     * @param array $params params for the GET request.
     * @return Instagram Response.
     */

    public function getSelf($params = array()) {
        $getParams = array(
            'endpoint' => '/' . $this->userId . '/' . self::ENDPOINT,
            'params' => array(
                'fields' => 'id,media_type,media_url,timestamp,permalink' // Add fields you need
            )
        );
    
        $response = $this->get($getParams);
        return $response;
    }
}

?>