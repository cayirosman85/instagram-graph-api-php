<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../../../vendor/autoload.php';

use Instagram\Controllers\UserController;
use Instagram\Controllers\PostController;
use Instagram\Controllers\UploadController;
use Instagram\Controllers\HashtagController;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

error_log("Request URI: " . $request_uri);
error_log("Request Method: " . $request_method);

switch ($request_uri) {

    case '/api/getTopMedia':
        if ($request_method === 'GET' || $request_method === 'POST') {
            $controller = new HashtagController();
            $controller->getTopMedia();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case '/api/searchHashtag':
        if ($request_method === 'GET' || $request_method === 'POST') {
            $controller = new HashtagController();
            $controller->searchHashtag();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case '/api/getRecentMedia':
        if ($request_method === 'GET' || $request_method === 'POST') {
            $controller = new HashtagController();
            $controller->getRecentMedia();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case '/api/get-profile':
        if ($request_method === 'GET' || $request_method === 'POST') {
            $controller = new UserController();
            $controller->getProfile();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case '/api/stories':
        if ($request_method === 'GET' || $request_method === 'POST') {
            $controller = new PostController();
            $controller->getStories();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case '/api/story-insights':
        if ($request_method === 'GET' || $request_method === 'POST') {
            $controller = new PostController();
            $controller->getStoryInsights();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case '/api/get-media-insights':
        if ($request_method === 'GET' || $request_method === 'POST') {
            $controller = new PostController();
            $controller->getMediaInsights();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case '/api/get-user-posts':
        if ($request_method === 'GET' || $request_method === 'POST') {
            $controller = new PostController();
            $controller->getUserPosts();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case '/api/create-reply':
        if ($request_method === 'POST') {
            $controller = new PostController();
            $controller->createReply();
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

    case '/api/publish-story':
        if ($request_method === 'POST') {
            $controller = new PostController();
            $controller->publishStory();
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

    case '/api/comment-visibility':
        if ($request_method === 'POST') {
            $controller = new PostController();
            $controller->toggleCommentVisibility();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case '/api/create-comment':
        if ($request_method === 'POST') {
            $controller = new PostController();
            $controller->createComment();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case '/api/delete-comment':
        if ($request_method === 'POST') {
            $controller = new PostController();
            $controller->deleteComment();
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