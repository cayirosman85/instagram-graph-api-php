<?php
namespace Instagram\Controllers;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Instagram\User\Media;
use Instagram\User\MediaPublish;
use Instagram\Container\Container;
use FFMpeg\FFProbe; // Add this line to import FFProbe correctly

class PostController {
    /**
     * Publish a post to Instagram with format and size validations
     */
    public function publishPost() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json; charset=UTF-8");

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
            'location_id' => $input['location_id'] ?? '',
        ];
        error_log("Post parameters: " . json_encode($postParams));

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
            if ($postParams['image_url']) {
                $this->validateImage($postParams['image_url']);
                $mediaType = 'image';
            } elseif ($postParams['video_url']) {
                $this->validateVideo($postParams['video_url']);
                $mediaType = 'video';
            }

            $media = new Media($config);
            error_log("Media object created with config: " . json_encode($config));

            $containerParams = [
                'caption' => $postParams['caption'],
                'location_id' => $postParams['location_id']
            ];

            if ($mediaType === 'image') {
                $containerParams['image_url'] = $postParams['image_url'];
            } elseif ($mediaType === 'video') {
                $containerParams['video_url'] = $postParams['video_url'];
                $containerParams['media_type'] = 'REELS';
            }
            error_log("Container parameters: " . json_encode($containerParams));

            $containerResponse = $media->create($containerParams);
            error_log("Container creation response (json): " . json_encode($containerResponse));

            $containerId = $containerResponse['id'] ?? null;
            if (empty($containerId)) {
                throw new \Exception("Failed to create media container: " . json_encode($containerResponse));
            }
            error_log("Extracted container ID: " . $containerId);

            $containerChecker = new Container([
                'user_id' => $config['user_id'],
                'access_token' => $config['access_token'],
                'container_id' => $containerId
            ]);
            $maxAttempts = 10;
            $attempt = 0;
            $status = 'IN_PROGRESS';

            while ($attempt < $maxAttempts && $status !== 'FINISHED') {
                $statusResponse = $containerChecker->getSelf();
                $status = $statusResponse['status_code'] ?? 'IN_PROGRESS';
                error_log("Container status (attempt $attempt): " . $status);

                if ($status === 'ERROR') {
                    throw new \Exception("Container processing failed: " . json_encode($statusResponse));
                }

                if ($status !== 'FINISHED') {
                    sleep(2);
                    $attempt++;
                }
            }

            if ($status !== 'FINISHED') {
                throw new \Exception("Container not ready after $maxAttempts attempts");
            }

            $mediaPublish = new MediaPublish($config);
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

    /**
     * Validate image URL for Instagram requirements
     */
    private function validateImage($url) {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        $allowedImageFormats = ['jpg', 'jpeg'];

        if (!in_array($extension, $allowedImageFormats)) {
            throw new \Exception("Invalid image format. Only JPG/JPEG allowed, got: $extension");
        }

        $headers = get_headers($url, true);
        $size = $headers['Content-Length'] ?? 0;
        if ($size > 8 * 1024 * 1024) {
            throw new \Exception("Image size exceeds 8 MB limit: " . round($size / (1024 * 1024), 2) . " MB");
        }

        $imageInfo = getimagesize($url);
        if (!$imageInfo) {
            throw new \Exception("Could not retrieve image dimensions");
        }
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $aspectRatio = $width / $height;

        if ($width < 320 || $width > 1440) {
            throw new \Exception("Image width must be between 320px and 1440px, got: $width");
        }
        if ($aspectRatio < 0.8 || $aspectRatio > 1.91) {
            throw new \Exception("Image aspect ratio must be between 4:5 (0.8) and 1.91:1 (1.91), got: " . round($aspectRatio, 2));
        }
    }

    /**
     * Validate video URL for Instagram requirements using php-ffmpeg
     */
    private function validateVideo($url) {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        $allowedVideoFormats = ['mp4', 'mov'];

        if (!in_array($extension, $allowedVideoFormats)) {
            throw new \Exception("Invalid video format. Only MP4/MOV allowed, got: $extension");
        }

        $headers = get_headers($url, true);
        $size = $headers['Content-Length'] ?? 0;
        if ($size > 4 * 1024 * 1024 * 1024) {
            throw new \Exception("Video size exceeds 4 GB limit: " . round($size / (1024 * 1024 * 1024), 2) . " GB");
        }

        // Use FFmpeg FFProbe to analyze the video
        $ffprobe = FFProbe::create();
        try {
            $streams = $ffprobe->streams($url);
            $videoStream = $streams->videos()->first();
            if (!$videoStream) {
                throw new \Exception("Could not retrieve video stream information");
            }

            $duration = $ffprobe->format($url)->get('duration');
            if ($duration < 3 || $duration > 60 * 60) {
                throw new \Exception("Video duration must be between 3 seconds and 60 minutes, got: " . round($duration, 2) . " seconds");
            }

            $width = $videoStream->get('width');
            $height = $videoStream->get('height');
            if ($width < 320 || $width > 1440) {
                throw new \Exception("Video width must be between 320px and 1440px, got: $width");
            }
            $aspectRatio = $width / $height;
            if ($aspectRatio < 0.8 || $aspectRatio > 1.91) {
                throw new \Exception("Video aspect ratio must be between 4:5 (0.8) and 1.91:1 (1.91), got: " . round($aspectRatio, 2));
            }

            $frameRate = eval("return " . $videoStream->get('r_frame_rate') . ";"); // e.g., "30/1" => 30
            if ($frameRate > 30) {
                throw new \Exception("Video frame rate must not exceed 30 FPS, got: $frameRate");
            }

            // Optional codec checks
            $videoCodec = $videoStream->get('codec_name');
            if (stripos($videoCodec, 'h264') === false) {
                error_log("Warning: Video codec may not be H.264, got: $videoCodec");
            }
            $audioStreams = $streams->audios();
            if ($audioStreams->count() > 0) {
                $audioCodec = $audioStreams->first()->get('codec_name');
                if (stripos($audioCodec, 'aac') === false) {
                    error_log("Warning: Audio codec may not be AAC, got: $audioCodec");
                }
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to validate video metadata: " . $e->getMessage());
        }
    }
}