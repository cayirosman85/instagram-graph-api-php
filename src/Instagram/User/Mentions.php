<?php


namespace Instagram\User;

// other classes we need to use
use Instagram\Instagram;

/**
 * Mentions
 *
 * Create comment on a comment the user is mentioned in.
 *     - Endpoint Format: POST /{ig-user-id}/mentions?media_id={media_id}&message={message}&access_token={access-token}
 *     - Endpoint Format: POST /{ig-user-id}/mentions?media_id={media_id}&comment_id={comment_id}&message={message}&access_token={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-user/mentions
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class Mentions extends User {
    /**
     * @const Instagram endpoint for the request.
     */
    const ENDPOINT = 'mentions';

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
     * Create a comment on a media post in which a user was mentioned.
     *
     * @param string $message comment to post.
     * @return Instagram response.
     */
    public function replyToMedia( $params ) {
         $postParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->userId . '/' . self::ENDPOINT,
            'params' => $params
        );

        // ig get request
        $response = $this->post( $postParams );

        // return response
        return $response;
    }

    /**
     * Create a comment on a comment in which a user was mentioned.
     *
     * @param string $message comment to post.
     * @return Instagram response.
     */
    public function replyToComment( $params ) {
         $postParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->userId . '/' . self::ENDPOINT,
            'params' => $params
        );

        // ig get request
        $response = $this->post( $postParams );

        // return response
        return $response;
    }
}

?>