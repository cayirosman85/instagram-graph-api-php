<?php
namespace Instagram\Controllers;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Instagram\User\Media;
use Instagram\User\MediaPublish;
use Instagram\Container\Container;
use Instagram\Comment\Comment;

class PostController {
  
    public function publishPost() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json; charset=UTF-8");

        // Increase PHP execution time limit to 300 seconds (5 minutes)
        ini_set('max_execution_time', 300);

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        error_log("Raw input received: " . json_encode($input));

        $config = [
            'user_id' => $input['user_id'] ?? '',
            'access_token' => $input['access_token'] ?? ''
        ];
        error_log("Config prepared: " . json_encode($config));

        $postParams = [
            'caption' => $input['caption'] ?? '',
            'image_url' => $input['image_url'] ?? '',
            'video_url' => $input['video_url'] ?? '',
            'children' => $input['children'] ?? [],
            'location_id' => $input['location_id'] ?? '',
            'user_tags' => $input['user_tags'] ?? []
        ];
        error_log("Post parameters: " . json_encode($postParams));

        if (empty($config['user_id']) || empty($config['access_token'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required parameters: user_id and access_token are required"]);
            return;
        }

        if (empty($postParams['image_url']) && empty($postParams['video_url']) && empty($postParams['children'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing media parameters: image_url, video_url, or children required"]);
            return;
        }

        try {
            $media = new Media($config);
            error_log("Media object initialized");
            $containerParams = [
                'caption' => $postParams['caption'],
                'location_id' => $postParams['location_id'],
                'user_tags' => $postParams['user_tags']
            ];

            if (!empty($postParams['children'])) {
                $uniqueChildren = array_unique($postParams['children']);
                if (count($uniqueChildren) < 2) {
                    throw new \Exception("Carousel albums require at least two unique media items.");
                }
                $childrenIds = [];

                foreach ($uniqueChildren as $index => $childUrl) {
                    error_log("Processing child URL #$index: $childUrl");
                    $extension = strtolower(pathinfo($childUrl, PATHINFO_EXTENSION));
                    $isImage = in_array($extension, ['jpg', 'jpeg']);
                    $isVideo = in_array($extension, ['mp4', 'mov']);

                    $childParams = [];
                    if ($isImage) {
                        $childParams = [
                            'image_url' => $childUrl,
                            'is_carousel_item' => true
                        ];
                        error_log("Child #$index is an image");
                    } elseif ($isVideo) {
                        $childParams = [
                            'video_url' => $childUrl,
                            'media_type' => 'REELS',
                            'is_carousel_item' => true
                        ];
                        error_log("Child #$index is a video");
                    } else {
                        throw new \Exception("Invalid child media format: $childUrl. Only JPG/JPEG or MP4/MOV allowed.");
                    }

                    // Create child container
                    $childContainer = $media->create($childParams);
                    $childId = $childContainer['id'] ?? null;
                    if (empty($childId)) {
                        throw new \Exception("Failed to create child container for $childUrl: " . json_encode($childContainer));
                    }
                    error_log("Child container created: " . $childId);

                    // Check child container status before proceeding to the next child
                    $childContainerChecker = new Container([
                        'user_id' => $config['user_id'],
                        'access_token' => $config['access_token'],
                        'container_id' => $childId
                    ]);
                    $childStatus = 'IN_PROGRESS';
                    $maxChildAttempts = 60; // ~5 minutes
                    $childAttempt = 0;

                    while ($childStatus !== 'FINISHED' && $childAttempt < $maxChildAttempts) {
                        $childStatusResponse = $childContainerChecker->getSelf();
                        $childStatus = $childStatusResponse['status_code'] ?? 'IN_PROGRESS';
                        error_log("Child #$index status (attempt $childAttempt): " . $childStatus);

                        if ($childStatus === 'ERROR') {
                            throw new \Exception("Child container processing failed for $childUrl: " . json_encode($childStatusResponse));
                        }

                        if ($childStatus !== 'FINISHED') {
                            sleep(5);
                            $childAttempt++;
                        }
                    }

                    if ($childStatus !== 'FINISHED') {
                        throw new \Exception("Child container for $childUrl not ready after 5 minutes");
                    }

                    $childrenIds[] = $childId;
                    error_log("Child #$index confirmed as FINISHED, proceeding to next child");
                }

                $containerParams['children'] = $childrenIds;
            } elseif ($postParams['image_url']) {
                $containerParams['image_url'] = $postParams['image_url'];
                error_log("Single image post prepared");
            } elseif ($postParams['video_url']) {
                $containerParams['video_url'] = $postParams['video_url'];
                $containerParams['media_type'] = 'REELS';
                error_log("Single video post prepared");
            }

            error_log("Container parameters: " . json_encode($containerParams));

            $container = $media->create($containerParams);
            $containerId = $container['id'] ?? null;
            if (empty($containerId)) {
                throw new \Exception("Failed to create media container: " . json_encode($container));
            }
            error_log("Container created: " . $containerId);

            $containerChecker = new Container([
                'user_id' => $config['user_id'],
                'access_token' => $config['access_token'],
                'container_id' => $containerId
            ]);
            error_log("Checking container status for ID: $containerId");

            $status = 'IN_PROGRESS';
            $maxAttempts = 60; // ~5 minutes with 5-second intervals
            $attempt = 0;

            while ($status !== 'FINISHED' && $attempt < $maxAttempts) {
                $statusResponse = $containerChecker->getSelf();
                $status = $statusResponse['status_code'] ?? 'IN_PROGRESS';
                error_log("Container status (attempt $attempt): " . $status);

                if ($status === 'ERROR') {
                    throw new \Exception("Container processing failed: " . json_encode($statusResponse));
                }

                if ($status !== 'FINISHED') {
                    sleep(5);
                    $attempt++;
                }
            }

            if ($status !== 'FINISHED') {
                throw new \Exception("Container not ready after 5 minutes");
            }

            $mediaPublish = new MediaPublish($config);
            error_log("Publishing media with container ID: $containerId");
            $publishResponse = $mediaPublish->create($containerId);
            $postId = $publishResponse['id'] ?? null;

            if (empty($postId)) {
                throw new \Exception("Failed to publish media: " . json_encode($publishResponse));
            }

            error_log("Post published successfully: Post ID = " . $postId);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'post_id' => $postId,
                'message' => 'Post published successfully'
            ]);
        } catch (\Exception $e) {
            error_log("Exception caught: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function publishStory() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json; charset=UTF-8");

        ini_set('max_execution_time', 300); // 5 minutes

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        error_log("Raw input received for story: " . json_encode($input));

        $config = [
            'user_id' => $input['user_id'] ?? '',
            'access_token' => $input['access_token'] ?? ''
        ];
        error_log("Config prepared: " . json_encode($config));

        $storyParams = [
            'image_url' => $input['image_url'] ?? '',
            'video_url' => $input['video_url'] ?? ''
        ];
        error_log("Story parameters: " . json_encode($storyParams));

        if (empty($config['user_id']) || empty($config['access_token'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required parameters: user_id and access_token are required"]);
            return;
        }

        if (empty($storyParams['image_url']) && empty($storyParams['video_url'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing media parameters: image_url or video_url required"]);
            return;
        }

        try {
            $media = new Media($config);
            error_log("Media object initialized for story");

            $containerParams = [
                'media_type' => 'STORIES' // Explicitly set media type to STORIES
            ];

            if ($storyParams['image_url']) {
                $containerParams['image_url'] = $storyParams['image_url'];
                error_log("Single image story prepared");
            } elseif ($storyParams['video_url']) {
                $containerParams['video_url'] = $storyParams['video_url'];
                error_log("Single video story prepared");
            }

            error_log("Container parameters for story: " . json_encode($containerParams));

            // Create the story container
            $container = $media->create($containerParams);
            $containerId = $container['id'] ?? null;
            if (empty($containerId)) {
                throw new \Exception("Failed to create story container: " . json_encode($container));
            }
            error_log("Story container created: " . $containerId);

            // Check container status
            $containerChecker = new Container([
                'user_id' => $config['user_id'],
                'access_token' => $config['access_token'],
                'container_id' => $containerId
            ]);
            error_log("Checking story container status for ID: $containerId");

            $status = 'IN_PROGRESS';
            $maxAttempts = 60; // ~5 minutes
            $attempt = 0;

            while ($status !== 'FINISHED' && $attempt < $maxAttempts) {
                $statusResponse = $containerChecker->getSelf();
                $status = $statusResponse['status_code'] ?? 'IN_PROGRESS';
                error_log("Story container status (attempt $attempt): " . $status);

                if ($status === 'ERROR') {
                    throw new \Exception("Story container processing failed: " . json_encode($statusResponse));
                }

                if ($status !== 'FINISHED') {
                    sleep(5);
                    $attempt++;
                }
            }

            if ($status !== 'FINISHED') {
                throw new \Exception("Story container not ready after 5 minutes");
            }

            // Publish the story
            $mediaPublish = new MediaPublish($config);
            error_log("Publishing story with container ID: $containerId");
            $publishResponse = $mediaPublish->create($containerId);
            $storyId = $publishResponse['id'] ?? null;

            if (empty($storyId)) {
                throw new \Exception("Failed to publish story: " . json_encode($publishResponse));
            }

            error_log("Story published successfully: Story ID = " . $storyId);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'story_id' => $storyId,
                'message' => 'Story published successfully'
            ]);
        } catch (\Exception $e) {
            error_log("Exception caught in story publishing: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function toggleCommentVisibility() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json; charset=UTF-8");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $userId = $data['user_id'] ?? '';
        $commentId = $data['comment_id'] ?? '';
        $accessToken = $data['access_token'] ?? '';
        $hide = $data['hide'] ?? false;

        if (!$userId || !$commentId || !$accessToken) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required parameters"]);
            return;
        }

        try {
            // Configure the Comment object
            $config = [
                'user_id' => $userId,
                'comment_id' => $commentId,
                'access_token' => $accessToken,
            ];

            // Instantiate the Comment class
            $comment = new Comment($config);

            // Toggle visibility (true to hide, false to show)
            $commentShowHide = $comment->setHide($hide);

            if ($commentShowHide) {
                error_log("Comment visibility toggled successfully: Comment ID = " . $commentId . ", Hidden = " . ($hide ? 'true' : 'false'));
                echo json_encode(["success" => true]);
            } else {
                throw new \Exception("Failed to toggle comment visibility");
            }
        } catch (\Exception $e) {
            error_log("Error toggling comment visibility: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Error toggling comment visibility: " . $e->getMessage()]);
        }
    }

    public function deleteComment() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json; charset=UTF-8");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $userId = $data['user_id'] ?? '';
        $commentId = $data['comment_id'] ?? '';
        $accessToken = $data['access_token'] ?? '';

        if (!$userId || !$commentId || !$accessToken) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required parameters"]);
            return;
        }

        try {
            // Configure the Comment object
            $config = [
                'user_id' => $userId,
                'comment_id' => $commentId,
                'access_token' => $accessToken,
            ];

            // Instantiate the Comment class
            $comment = new Comment($config);

            // Delete the comment
            $commentDeleted = $comment->remove();

            if ($commentDeleted) {
                error_log("Comment deleted successfully: Comment ID = " . $commentId);
                echo json_encode(["success" => true]);
            } else {
                throw new \Exception("Failed to delete comment");
            }
        } catch (\Exception $e) {
            error_log("Error deleting comment: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Error deleting comment: " . $e->getMessage()]);
        }
    }
}