<?php

header("Content-Type: application/json");

$request_uri = $_SERVER['REQUEST_URI'];

// Load Controllers Based on Route
switch ($request_uri) {
    case '/api/users':
        require_once '../controllers/UserController.php';
        $controller = new UserController();
        $controller->getUsers();
        break;

    case '/api/posts':
        require_once '../controllers/PostController.php';
        $controller = new PostController();
        $controller->getPosts();
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Not Found"]);
        break;
}
