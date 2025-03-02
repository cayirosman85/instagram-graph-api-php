<?php
namespace Instagram\Controllers;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Instagram\User\Media;
use Instagram\User\MediaPublish;
use Instagram\Container\Container;

class PostController {
    /**
     * Publish a post to Instagram
     */
    public function publishPost() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json; charset=UTF-8");

        // Handle OPTIONS preflight request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // Get raw POST data
        $input = json_decode(file_get_contents('php://input'), true);
        error_log("Raw input received: " . json_encode($input));

        $config = array(
            'user_id' => $input['user_id'] ?? '',
            'access_token' => $input['access_token'] ?? ''
        );
        error_log("Config prepared: " . json_encode($config));

        $postParams = array(
            'caption' => $input['caption'] ?? '',
            'image_url' => $input['image_url'] ?? '',
            'video_url' => $input['video_url'] ?? '',
            'location_id' => $input['location_id'] ?? '',
        );
        error_log("Post parameters: " . json_encode($postParams));

        // Validate required parameters
        if (empty($config['user_id']) || empty($config['access_token'])) {
            error_log("Validation failed: Missing user_id or access_token");
            http_response_code(400);
            echo json_encode(["error" => "Missing required parameters: user_id and access_token are required"]);
            return;
        }

        if (empty($postParams['image_url']) && empty($postParams['video_url'])) {
            error_log("Validation failed: Missing image_url or video_url");
            http_response_code(400);
            echo json_encode(["error" => "Missing media parameters: image_url or video_url required"]);
            return;
        }

        try {
            $media = new Media($config);
            error_log("Media object created with config: " . json_encode($config));

            $containerParams = array(
                'caption' => $postParams['caption'],
                'location_id' => $postParams['location_id']
            );

            if ($postParams['image_url']) {
                $containerParams['image_url'] = $postParams['image_url'];
            } elseif ($postParams['video_url']) {
                $containerParams['video_url'] = $postParams['video_url'];
                $containerParams['media_type'] = 'VIDEO';
            }
            error_log("Container parameters: " . json_encode($containerParams));

            // Create media container
            $containerResponse = $media->create($containerParams);
            error_log("Container creation response (json): " . json_encode($containerResponse));
            error_log("Container creation response (raw): " . var_export($containerResponse, true));

            // Extract container ID from array
            $containerId = $containerResponse['id'] ?? null;
            error_log("Extracted container ID: " . ($containerId ?? 'null'));

            if (empty($containerId)) {
                error_log("Container creation failed: Missing container ID");
                throw new \Exception("Failed to create media container: " . json_encode($containerResponse));
            }

            // Check container status using the Container class
            $containerConfig = array(
                'user_id' => $config['user_id'],
                'access_token' => $config['access_token'],
                'container_id' => $containerId
            );
            $containerChecker = new Container($containerConfig);
            $maxAttempts = 10;
            $attempt = 0;
            $status = 'IN_PROGRESS';

            while ($attempt < $maxAttempts && $status !== 'FINISHED') {
                $statusResponse = $containerChecker->getSelf();
                error_log("Container status response (attempt $attempt): " . json_encode($statusResponse));
                error_log("Container status response (raw): " . var_export($statusResponse, true));

                // Check status_code as an array key
                $status = $statusResponse['status_code'] ?? 'IN_PROGRESS';
                error_log("Container status: " . $status);

                if ($status === 'ERROR') {
                    error_log("Container processing failed");
                    throw new \Exception("Container processing failed: " . json_encode($statusResponse));
                }

                if ($status !== 'FINISHED') {
                    sleep(2);
                    $attempt++;
                }
            }

            if ($status !== 'FINISHED') {
                error_log("Container not finished after $maxAttempts attempts");
                throw new \Exception("Container not ready after $maxAttempts attempts: " . json_encode($statusResponse));
            }

            // Publish the container
            $mediaPublish = new MediaPublish($config);
            error_log("MediaPublish object created with config: " . json_encode($config));

            $publishResponse = $mediaPublish->create($containerId);
            error_log("Publish response (json): " . json_encode($publishResponse));
            error_log("Publish response (raw): " . var_export($publishResponse, true));

            // Extract post ID from array
            $postId = $publishResponse['id'] ?? null;
            error_log("Extracted post ID: " . ($postId ?? 'null'));

            if (empty($postId)) {
                error_log("Publish failed: Missing post ID");
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
}