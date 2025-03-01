<?php

header("Content-Type: application/json");

// Correct path to autoload.php
require_once __DIR__ . '/../../../vendor/autoload.php';

use Instagram\Controllers\UserController;
use Instagram\Controllers\PostController;

$request_uri = $_SERVER['REQUEST_URI'];

switch ($request_uri) {
    case '/api/users':
        $controller = new UserController();
        $controller->getUsers();
        break;

    case '/api/posts':
        $controller = new PostController();
        $controller->getPosts();
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Not Found"]);
        break;
}
