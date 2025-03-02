<?php


namespace Instagram\Page;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;

/**
 * Page
 *
 * Get the IG user connected to a Facebook Page.
 *     - Endpoint Format: GET /{page-id}?fields=instagram_business_account&access_token={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/page
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class Page Extends Instagram {
    /**
     * @var string $pageId page id to use with requests.
     */
    protected $pageId;

    /**
     * @var array $fields a list of all the fields we are requesting to get back.
     */
    protected $fields = array(
        Fields::INSTAGRAM_BUSINESS_ACCOUNT
    );

    /**
     * Contructor for instantiating a new object.
     *
     * @param array $config for the class.
     * @return void
     */
    public function __construct( $config ) {
        // call parent for setup
        parent::__construct( $config );

        // store the page id
        $this->pageId = $config['page_id'];
    }

    /**
     * Get the Instagram user connected to the Facebook page.
     *
     * @param array $params params for the GET request.
     * @return Instagram response.
     */
    public function getSelf( $params = array() ) {
        $getParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->pageId,
            'params' => $params ? $params : Params::getFieldsParam( $this->fields )
        );

        // ig get request
        $response = $this->get( $getParams );

        // return response
        return $response;
    }
}

?>