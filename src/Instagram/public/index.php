<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Correct path to autoload.php
require_once __DIR__ . '/../../../vendor/autoload.php';

use Instagram\Controllers\UserController;
use Instagram\Controllers\PostController;

// Handle API requests
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
error_log("Request URI: " . $request_uri);  // Log the request URI
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
