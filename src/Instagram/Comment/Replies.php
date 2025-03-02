<?php


namespace Instagram\Comment;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;

/**
 * Replies
 *
 * Replies on a specific media.
 *     - Endpoint Format: GET /{ig-comment-id}/replies?fields={fields}&access_token={access-token}
 *     - Endpoint Format: POST /{ig-comment-id}/replies?message={message}&access_token={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-comment/replies
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class Replies extends Comment {
    /**
     * @const Instagram endpoint for the request.
     */
    const ENDPOINT = 'replies';

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
     * Create a reply on a comment.
     *
     * @param string $message reply to post.
     * @return Instagram Response.
     */
    public function create( $message ) {
         $postParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->commentId . '/' . self::ENDPOINT,
            'params' => array(
                Params::MESSAGE => $message
            )
        );

        // ig get request
        $response = $this->post( $postParams );

        // return response
        return $response;
    }

    /**
     * Get replies for a comment.
     *
     * @param array $paramsparams for the GET request.
     * @return Instagram response.
     */
    public function getSelf( $params = array() ) {
        $getParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->commentId . '/' . $this->endpoint,
            'params' => $params ? $params : Params::getFieldsParam( Fields::getDefaultCommentFields( false ) )
        );

        // ig get request
        $response = $this->get( $getParams );

        // return response
        return $response;
    }
}

?>