<?php


namespace Instagram\User;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;

/**
 * Mentioned Comment
 *
 * Get mentioned comment for user.
 *     - Endpoint Format: GET /{ig-user-id}?fields=mentioned_comment.comment_id({comment-id}){{fields}}&access_toke={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-user/mentioned_comment
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class MentionedComment extends User {
    /**
     * @var integer $commentId id of the instagram comment.
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
     * Get comment user is mentioned in.
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
            $params[Params::FIELDS] = Fields::MENTIONED_COMMENT . '.' . Fields::COMMENT_ID . '(' . $this->commentId . '){' . 
                Fields::getDefaultCommentFields() . ',' .
                Fields::REPLIES . '{' .
                    Fields::getDefaultRepliesFields() .
                '}' .
            '}';

            // return our params
            return $params;
        }
    }
}

?>