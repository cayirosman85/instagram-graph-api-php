<?php

namespace Instagram\Services;

use Instagram\User\BusinessDiscovery;

class BusinessDiscoveryService {
    private $businessDiscovery;

    public function __construct($config) {
        $this->businessDiscovery = new BusinessDiscovery($config);
    }

    public function getSelf() {
        return $this->businessDiscovery->getSelf();
    }
}
