<?php


namespace Instagram\Comment;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;

/**
 * Comment
 *
 * Get comments on a specific media.
 *     - Endpoint Format: GET /{ig-comment-id}?fields={fields}&access_token={access-token}
 *     - Endpoint Format: POST /{ig-comment-id}?hide={hide}&access_token={access-token}
 *     - Endpoint Format: DELETE /{ig-comment-id}?access_token={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-comment
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class Comment extends Instagram {
    /**
     * @var integer $commentId Instagram id of the comment.
     */
    protected $commentId;

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
        $this->commentId = $config['comment_id'];
    }

    /**
     * Get a comment.
     *
     * @param array $params params for the GET request.
     * @return Instagram response.
     */
    public function getSelf( $params = array() ) {
        $getParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->commentId,
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
            $params[Params::FIELDS] = Fields::getDefaultCommentFields() . ',' .
                Fields::REPLIES . '{' .
                    Fields::getDefaultCommentFields() .
                '}'
            ;

            // return our params
            return $params;
        }
    }

    /**
     * Set hide parameter on a comment.
     *
     * @param boolean $hide show or hide the comment with true|false.
     * @return Instagram Response.
     */
    public function setHide( $hide ) {
        $postParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->commentId,
            'params' => array(
                Params::HIDE => $hide
            )
        );

        // ig get request
        $response = $this->post( $postParams );

        // return response
        return $response;
    }

    /**
     * Delete a comment.
     *
     * @return Instagram Response.
     */
    public function remove() {
        $postParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->commentId
        );

        // ig get request
        $response = $this->delete( $postParams );

        // return response
        return $response;
    }
}

?>