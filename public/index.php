<?php
// public/index.php

use Instagram\User\BusinessDiscovery;

$config = array( // instantiation config params
    'user_id' => '<IG_USER_ID>',
    'username' => '<USERNAME>', // string of the Instagram account username to get data on
    'access_token' => '<ACCESS_TOKEN>',
);

// instantiate business discovery for a user
$businessDiscovery = new BusinessDiscovery( $config );

// initial business discovery
$userBusinessDiscovery = $businessDiscovery->getSelf();