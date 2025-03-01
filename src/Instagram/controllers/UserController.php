<?php
namespace Instagram\Controllers;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Instagram\User\BusinessDiscovery;

class UserController {
    public function getUsers() {
        $config = array(
            'user_id' => '<IG_USER_ID>',
            'username' => '<USERNAME>',
            'access_token' => '<ACCESS_TOKEN>',
        );

        $businessDiscovery = new BusinessDiscovery($config);
        $userBusinessDiscovery = $businessDiscovery->getSelf();

        echo json_encode($userBusinessDiscovery);
    }
}
