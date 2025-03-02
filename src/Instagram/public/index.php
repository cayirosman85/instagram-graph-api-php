<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Correct path to autoload.php (adjust based on your project structure)
require_once __DIR__ . '/../../../vendor/autoload.php';

use Instagram\Controllers\UserController;
use Instagram\Controllers\PostController;
use Instagram\Controllers\UploadController;

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the request URI and method
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

error_log("Request URI: " . $request_uri);  // Log the request URI
error_log("Request Method: " . $request_method);  // Log the request method

// Route the request
switch ($request_uri) {
    case '/api/users':
        if ($request_method === 'GET' || $request_method === 'POST') {
            $controller = new UserController();
            $controller->getUsers();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case '/api/publish-post':
        if ($request_method === 'POST') {
            $controller = new PostController();
            $controller->publishPost();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case '/api/upload':
        if ($request_method === 'POST') {
            $controller = new UploadController();
            $controller->uploadFile();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Not Found"]);
        break;
}