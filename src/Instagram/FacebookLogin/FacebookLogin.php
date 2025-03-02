<?php


namespace Instagram\FacebookLogin;

// other classes we need to use
use Instagram\Instagram;
use Instagram\Request\Request;
use Instagram\Request\Params;
use Instagram\Request\ResponseTypes;

/**
 * FacebookLogin
 *
 * Core functionality for login dialog.
 *     - Facebook Docs: https://developers.facebook.com/docs/facebook-login/guides/advanced/manual-flow/
 *
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class FacebookLogin extends Instagram {
    /**
     * @const debug token endpoint
     */
    const ENDPOINT = 'dialog/oauth';

    /**
     * @var integer $appId Facebook application id.
     */
    protected $appId;

    /**
     * @var integer $appId Facebook application secret.
     */
    protected $appSecret;

    /**
     * Contructor for instantiating a new object.
     *
     * @param array $config for the class.
     * @return void
     */
    public function __construct( $config = array() ) {
        // call parent for setup
        parent::__construct( $config );

        // set the application id
        $this->appId = $config['app_id'];

        // set the application secret
        $this->appSecret = $config['app_secret'];
    }

    /**
     * Get the url for a user to prompt them with the login dialog.
     *
     * @param string $redirectUri uri the user gets sent to after logging in with facebook.
     * @param array $permissions array of the permissions you want to request from the user.
     * @param string $state this gets passed back from facebook in the redirect uri.
     * @return Instagram response.
     */
    public function getLoginDialogUrl( $redirectUri, $permissions, $state = '' ) {
        $params = array( // params required to generate the login url
            Params::CLIENT_ID => $this->appId,
            Params::REDIRECT_URI => $redirectUri,
            Params::STATE => $state,
            Params::SCOPE => Params::commaImplodeArray( $permissions ),
            Params::RESPONSE_TYPE => ResponseTypes::CODE,
        );

        // return the login dialog url
        return Request::BASE_AUTHORIZATION_URL . '/' . $this->graphVersion . '/' . self::ENDPOINT . '?' . http_build_query( $params );;
    }    
}

?>