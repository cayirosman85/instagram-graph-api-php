<?php
namespace Instagram\Controllers;
require_once '../../vendor/autoload.php'; // change path as needed
use Instagram\User\BusinessDiscovery; 

class UserController {
    public function getUsers() {
        $config = array(
            'user_id' => '<IG_USER_ID>',
            'username' => '<USERNAME>',
            'access_token' => '<ACCESS_TOKEN>',
        );
        
 
        // instantiate business discovery for a user
$businessDiscovery = new BusinessDiscovery( $config );

// initial business discovery
$userBusinessDiscovery = $businessDiscovery->getSelf();

        echo json_encode($userBusinessDiscovery);
    }
}
