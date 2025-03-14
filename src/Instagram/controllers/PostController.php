<?php
namespace Instagram\Controllers;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Instagram\User\Media;
use Instagram\User\MediaPublish;
use Instagram\Container\Container;
use Instagram\Comment\Comment;
use Instagram\Media\Comments;
use Instagram\Comment\Replies;
use Instagram\Media\Insights;
use Instagram\User\Stories;

use Instagram\User\UserPosts;

class PostController {
  

    public function getUserPosts() {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        $username = $input['username'] ?? null;
        $accessToken = $input['access_token'] ?? null;
        $limit = $input['limit'] ?? 5;
        $after = $input['after'] ?? null;
    
        if (!$userId || !$username || !$accessToken) {
          http_response_code(400);
          echo json_encode(["error" => "Missing required parameters"]);
          return;
        }
    
        $userPosts = new UserPosts([
          'user_id' => $userId,
          'username' => $username,
          'access_token' => $accessToken,
        ]);
    
        $params = ['limit' => $limit];
        if ($after) {
          $params['after'] = $after;
        }
    
        $response = $userPosts->getSelf($params);
    
        if (isset($response['error'])) {
          http_response_code(500);
          echo json_encode($response);
          return;
        }
    
        $result = [
          'posts' => $response['business_discovery']['media']['data'] ?? [],
          'paging' => $response['business_discovery']['media']['paging'] ?? [],
        ];
    
        echo json_encode($result);
      }
  
      public function publishPost() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json; charset=UTF-8");

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

                    $childContainer = $media->create($childParams);
                    $childId = $childContainer['id'] ?? null;
                    if (empty($childId)) {
                        throw new \Exception("Failed to create child container for $childUrl: " . json_encode($childContainer));
                    }
                    error_log("Child container created: " . $childId);

                    $childContainerChecker = new Container([
                        'user_id' => $config['user_id'],
                        'access_token' => $config['access_token'],
                        'container_id' => $childId
                    ]);
                    $childStatus = 'IN_PROGRESS';
                    $maxChildAttempts = 60;
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
            $maxAttempts = 60;
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

        ini_set('max_execution_time', 300);

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
                'media_type' => 'STORIES'
            ];

            if ($storyParams['image_url']) {
                $containerParams['image_url'] = $storyParams['image_url'];
                error_log("Single image story prepared");
            } elseif ($storyParams['video_url']) {
                $containerParams['video_url'] = $storyParams['video_url'];
                error_log("Single video story prepared");
            }

            error_log("Container parameters for story: " . json_encode($containerParams));

            $container = $media->create($containerParams);
            $containerId = $container['id'] ?? null;
            if (empty($containerId)) {
                throw new \Exception("Failed to create story container: " . json_encode($container));
            }
            error_log("Story container created: " . $containerId);

            $containerChecker = new Container([
                'user_id' => $config['user_id'],
                'access_token' => $config['access_token'],
                'container_id' => $containerId
            ]);
            error_log("Checking story container status for ID: $containerId");

            $status = 'IN_PROGRESS';
            $maxAttempts = 60;
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
            $config = [
                'user_id' => $userId,
                'comment_id' => $commentId,
                'access_token' => $accessToken,
            ];

            $comment = new Comment($config);
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
            $config = [
                'user_id' => $userId,
                'comment_id' => $commentId,
                'access_token' => $accessToken,
            ];

            $comment = new Comment($config);
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

    public function createComment() {
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
        $mediaId = $data['media_id'] ?? '';
        $accessToken = $data['access_token'] ?? '';
        $commentText = $data['comment'] ?? '';

        if (!$userId || !$mediaId || !$accessToken || !$commentText) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required parameters"]);
            return;
        }

        try {
            $instagram = new \Instagram\Instagram([
                'access_token' => $accessToken,
            ]);

            $response = $instagram->post([
                'endpoint' => "/{$mediaId}/comments",
                'params' => [
                    'message' => $commentText,
                ],
            ]);

            if (isset($response['id'])) {
                error_log("Comment created successfully: Media ID = " . $mediaId . ", Comment ID = " . $response['id']);
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "comment_id" => $response['id'],
                    "message" => "Comment posted successfully"
                ]);
            } else {
                throw new \Exception("Failed to create comment: " . json_encode($response));
            }
        } catch (\Exception $e) {
            error_log("Error creating comment: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "error" => "Error creating comment: " . $e->getMessage()
            ]);
        }
    }

    public function createReply() {
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
        $replyText = $data['reply'] ?? '';

        if (!$userId || !$commentId || !$accessToken || !$replyText) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required parameters"]);
            return;
        }

        try {
            $config = [
                'user_id' => $userId,
                'comment_id' => $commentId,
                'access_token' => $accessToken,
            ];

            $replies = new Replies($config);
            $response = $replies->create($replyText);

            if (isset($response['id'])) {
                error_log("Reply created successfully: Comment ID = " . $commentId . ", Reply ID = " . $response['id']);
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "reply_id" => $response['id'],
                    "message" => "Reply posted successfully"
                ]);
            } else {
                throw new \Exception("Failed to create reply: " . json_encode($response));
            }
        } catch (\Exception $e) {
            error_log("Error creating reply: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "error" => "Error creating reply: " . $e->getMessage()
            ]);
        }
    }

    public function getStoryInsights() {
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
        $mediaId = $data['media_id'] ?? '';
        $accessToken = $data['access_token'] ?? '';

        error_log(" insights Data." . $userId . " " . $mediaId . " " . $accessToken);


        if (!$userId || !$mediaId || !$accessToken) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Missing required parameters"]);
            return;
        }

        try {
            $config = [
                'user_id' => $userId,
                'media_id' => $mediaId,
                'media_type' => 'STORY',
                'access_token' => $accessToken,
            ];

            $insights = new Insights($config);
            $mediaInsights = $insights->getSelf();

            error_log("Raw insights response for story ID $mediaId: " . json_encode($mediaInsights));

            if (isset($mediaInsights['error'])) {
                if ($mediaInsights['error']['code'] === 10 && strpos($mediaInsights['error']['message'], "Not enough viewers") !== false) {
                    error_log("Story ID $mediaId has insufficient viewers for insights.");
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'insights' => [],
                        'message' => 'No insights available due to insufficient viewers'
                    ]);
                    return;
                } else {
                    throw new \Exception("API error: " . $mediaInsights['error']['message']);
                }
            }

            if (empty($mediaInsights['data'])) {
                error_log("No insights data returned for story ID: $mediaId, but no error present.");
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'insights' => [],
                    'message' => 'No insights available for this story'
                ]);
                return;
            }

            error_log("Story insights fetched successfully for media ID: " . $mediaId);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'insights' => $mediaInsights['data'],
                'message' => 'Story insights retrieved successfully'
            ]);
        } catch (\Exception $e) {
            error_log("Error fetching story insights: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => "Error fetching story insights: " . $e->getMessage()
            ]);
        }
   
   
   
   
    }

    public function getStories() {
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
        $accessToken = $data['access_token'] ?? '';
    
        error_log("Stories request data: user_id = " . $userId . ", access_token = " . $accessToken);
    
        if (!$userId || !$accessToken) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Missing required parameters: user_id and access_token are required"]);
            return;
        }
    
        try {
            $config = [
                'user_id' => $userId,
                'access_token' => $accessToken,
            ];
    
            $stories = new Stories($config);
            $response = $stories->getSelf(); // Fetch the user's stories
    
            error_log("Raw stories response: " . json_encode($response));
    
            if (isset($response['error'])) {
                throw new \Exception("API error: " . $response['error']['message']);
            }
    
            $storiesData = $response['data'] ?? [];
            if (empty($storiesData)) {
                error_log("No stories available for user ID: " . $userId);
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'stories' => [],
                    'message' => 'No stories available at this time'
                ]);
                return;
            }
    
            error_log("Stories fetched successfully for user ID: " . $userId);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'stories' => $storiesData,
                'message' => 'Stories retrieved successfully'
            ]);
        } catch (\Exception $e) {
            error_log("Error fetching stories: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => "Error fetching stories: " . $e->getMessage()
            ]);
        }
    }

    public function getMediaInsights() {
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
        $mediaId = $data['media_id'] ?? '';
        $accessToken = $data['access_token'] ?? '';
        $mediaType = $data['media_type'] ?? 'UNKNOWN';
    
        if (!$userId || !$mediaId || !$accessToken) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Missing required parameters"]);
            return;
        }
    
        try {
            $config = [
                'user_id' => $userId,
                'media_id' => $mediaId,
                'media_type' => $mediaType,
                'access_token' => $accessToken,
            ];
    
            $insightsObj = new Insights($config);
            $response = $insightsObj->getSelf();
    
            if (isset($response['error'])) {
                throw new \Exception("Failed to fetch insights: " . json_encode($response['error']));
            }
    
            error_log("Insights fetched successfully for media ID: " . $mediaId);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'insights' => $response['data'] ?? [],
                'message' => 'Insights retrieved successfully'
            ]);
        } catch (\Exception $e) {
            error_log("Error fetching insights: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => "Error fetching insights: " . $e->getMessage()
            ]);
        }
    }
}