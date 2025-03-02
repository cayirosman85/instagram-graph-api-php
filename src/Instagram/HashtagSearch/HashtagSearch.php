<?php


namespace Instagram\HashtagSearch;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;

/**
 * Hashtag Search.
 *
 * Get the id for a hashtag.
 *     - Endpoint Format: GET /ig_hashtag_search?user_id={user-id}&q={q}&access_toke={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-hashtag-search
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class HashtagSearch extends Instagram {
    /**
     * @const Instagram endpoint for the request.
     */
    const ENDPOINT = 'ig_hashtag_search';

    /**
     * @var integer $userId Instagram user id making the api request.
     */
    protected $userId;

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
        $this->userId = $config['user_id'];
    }

    /**
     * Get info on a hashtag.
     *
     * @param string $hashtagName name of hashtag to get ID for.
     * @return Instagram response.
     */
    public function getSelf( $hashtagName ) {
        $getParams = array( // parameters for our endpoint
            'endpoint' => '/' . self::ENDPOINT,
            'params' => array(
                Params::USER_ID => $this->userId,
                Params::Q => $hashtagName
            )
        );

        // ig get request
        $response = $this->get( $getParams );

        // return response
        return $response;
    }
}

?>