<?php


namespace Instagram\Container;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;

/**
 * Container
 *
 * Get info on a container.
 *     - Endpoint Format: GET /{ig-container-id}/?fields={fields}&access_token={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-container
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class Container extends Instagram {
    /**
     * @var integer $containerId Instagram container id for publishing media.
     */
    protected $containerId;

     /**
     * @var array $fields a list of all the fields we are requesting to get back.
     */
    protected $fields = array(
        Fields::ID,
        Fields::STATUS,
        Fields::STATUS_CODE
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
        
        // store the user id
        $this->containerId = $config['container_id'];
    }

    /**
     * Get the status of a container.
     *
     * @param array $params params for the GET request.
     * @return Instagram response.
     */
    public function getSelf( $params = array() ) {
        $getParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->containerId,
            'params' => $params ? $params : Params::getFieldsParam( $this->fields )
        );

        // ig get request
        $response = $this->get( $getParams );

        // return response
        return $response;
    }
}

?>