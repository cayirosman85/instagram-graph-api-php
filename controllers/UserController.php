<?php

require_once '../services/BusinessDiscoveryService.php';

use Instagram\Services\BusinessDiscoveryService;

class UserController {
    private $businessService;

    public function __construct() {
        $config = require '../config/config.php';
        $this->businessService = new BusinessDiscoveryService($config['instagram']);
    }

    public function getUsers() {
        $data = $this->businessService->getSelf();
        echo json_encode($data);
    }
}
