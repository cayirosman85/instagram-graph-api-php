<?php


namespace Instagram\User;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Metric;
use Instagram\Request\Period;

/**
 * Insights
 *
 * Get insights on the IG users business account.
 *     - Endpoint Format: GET /{ig-user-id}/insights?metric={metric}&period={period}&since={since}&until={until}&access_token={access-token}
 *     - Facebook docs: https://developers.facebook.com/docs/instagram-api/reference/ig-user/insights
 * 
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class Insights extends User {
    /**
     * @const Instagram endpoint for the request.
     */
    const ENDPOINT = 'insights';

    /**
     * @var array $metric a list of all the metrics we are requesting to get back.
     */
    protected $metrics = array(
        Metric::IMPRESSIONS,
        Metric::REACH
    );

    /**
     * @var array $period the period we are requesting to get back.
     */
    protected $period = Period::DAY;

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
     * Get insights on the user..
     *
     * @param array $params params for the GET request.
     * @return Instagram response.
     */
    public function getSelf( $params = array() ) {
        $getParams = array( // parameters for our endpoint
            'endpoint' => '/' . $this->userId . '/' . self::ENDPOINT,
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
            // set the metrics param
            $params[Params::METRIC] = Params::commaImplodeArray( $this->metrics );

            // set the period param
            $params[Params::PERIOD] = $this->period;

            // return our params
            return $params;
        }
    }    
}

?>