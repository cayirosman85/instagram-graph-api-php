<?php
namespace Instagram\Controllers;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Instagram\HashtagSearch\HashtagSearch;
use Instagram\Hashtag\RecentMedia;
use Instagram\Hashtag\TopMedia;

class HashtagController
{
    public function __construct()
    {
        error_log("HashtagController constructed");
    }

    public function searchHashtag()
    {
        error_log("Entered searchHashtag function");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json; charset=UTF-8");

        $input = json_decode(file_get_contents('php://input'), true);
        error_log("Raw input: " . file_get_contents('php://input'));
        error_log("Decoded input: " . json_encode($input));

        $hashtagName = $input['q'] ?? null;
        $userId = $input['user_id'] ?? null;
        $accessToken = $input['access_token'] ?? null;

        error_log("Hashtag Name: " . ($hashtagName ?? 'null'));
        error_log("User ID: " . ($userId ?? 'null'));
        error_log("Access Token: " . ($accessToken ?? 'null'));

        if (empty($userId) || empty($accessToken) || empty($hashtagName)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required parameters: user_id, access_token, or q"]);
            return;
        }

        try {
            $hashtagSearch = new HashtagSearch([
                'user_id' => $userId,
                'access_token' => $accessToken,
            ]);
            $response = $hashtagSearch->getSelf($hashtagName);
            error_log("HashtagSearch response: " . json_encode($response));
            echo json_encode($response);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error searching hashtag: " . $e->getMessage()]);
            error_log("Exception in searchHashtag: " . $e->getMessage());
        }
    }

    public function getRecentMedia()
    {
        error_log("Entered getRecentMedia function");

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json; charset=UTF-8");

        $input = json_decode(file_get_contents('php://input'), true);
        error_log("Raw input: " . file_get_contents('php://input'));
        error_log("Decoded input: " . json_encode($input));

        $hashtagId = $input['hashtag_id'] ?? null;
        $userId = $input['user_id'] ?? null;
        $accessToken = $input['access_token'] ?? null;

        error_log("Hashtag ID: " . ($hashtagId ?? 'null'));
        error_log("User ID: " . ($userId ?? 'null'));
        error_log("Access Token: " . ($accessToken ?? 'null'));

        if (empty($userId) || empty($accessToken) || empty($hashtagId)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required parameters: user_id, access_token, or hashtag_id"]);
            return;
        }

        try {
            $recentMedia = new RecentMedia([
                'user_id' => $userId,
                'hashtag_id' => $hashtagId,
                'access_token' => $accessToken,
            ]);
            $response = $recentMedia->getSelf();
            error_log("RecentMedia response: " . json_encode($response));
            echo json_encode($response);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error fetching recent media: " . $e->getMessage()]);
            error_log("Exception in getRecentMedia: " . $e->getMessage());
        }
    }

    public function getTopMedia()
    {
        error_log("Entered getTopMedia function");

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json; charset=UTF-8");

        $input = json_decode(file_get_contents('php://input'), true);
        error_log("Raw input: " . file_get_contents('php://input'));
        error_log("Decoded input: " . json_encode($input));

        $hashtagId = $input['hashtag_id'] ?? null;
        $userId = $input['user_id'] ?? null;
        $accessToken = $input['access_token'] ?? null;

        error_log("Hashtag ID: " . ($hashtagId ?? 'null'));
        error_log("User ID: " . ($userId ?? 'null'));
        error_log("Access Token: " . ($accessToken ?? 'null'));

        if (empty($userId) || empty($accessToken) || empty($hashtagId)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required parameters: user_id, access_token, or hashtag_id"]);
            return;
        }

        try {
            $topMedia = new TopMedia([
                'user_id' => $userId,
                'hashtag_id' => $hashtagId,
                'access_token' => $accessToken,
            ]);
            $response = $topMedia->getSelf();
            error_log("TopMedia response: " . json_encode($response));
            echo json_encode($response);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error fetching top media: " . $e->getMessage()]);
            error_log("Exception in getTopMedia: " . $e->getMessage());
        }
    }
}
?>