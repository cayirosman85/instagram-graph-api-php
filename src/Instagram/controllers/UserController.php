<?php
namespace Instagram\Controllers;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Instagram\User\BusinessDiscovery;

class UserController {
    public function getUsers() {

        error_log("Entered getUsers function"); 

        // Enable CORS
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json; charset=UTF-8");

        // Get config from request (supporting both GET and POST)
        $config = array(
            'user_id' => $_REQUEST['user_id'] ?? '',
            'username' => $_REQUEST['username'] ?? '',
            'access_token' => $_REQUEST['access_token'] ?? ''
        );
        error_log("Entered getUsers function".json_encode($config)); 

        // Validate required parameters
        if (empty($config['user_id']) || empty($config['username']) || empty($config['access_token'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required parameters"]);
            return;
        }

        try {
            $businessDiscovery = new BusinessDiscovery($config);
            $userBusinessDiscovery = $businessDiscovery->getSelf();


            echo json_encode($userBusinessDiscovery);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }
}
