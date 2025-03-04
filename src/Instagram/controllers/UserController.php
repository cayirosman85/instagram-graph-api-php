<?php
namespace Instagram\Controllers;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Instagram\User\BusinessDiscovery;

class UserController {

    public function getUsers() {
        error_log("Entered getUsers function");

        // Enable CORS
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json; charset=UTF-8");

        // Get config from request (supporting both GET and POST)
        $config = array(
            'user_id' => $_REQUEST['user_id'] ?? '',
            'username' => $_REQUEST['username'] ?? '',
            'access_token' => $_REQUEST['access_token'] ?? ''
        );
        error_log("Config: " . json_encode($config));

        // Validate required parameters
        if (empty($config['user_id']) || empty($config['username']) || empty($config['access_token'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required parameters"]);
            return;
        }

        try {
            $businessDiscovery = new BusinessDiscovery($config);
            $userBusinessDiscovery = $businessDiscovery->getSelf();
            echo json_encode($userBusinessDiscovery);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    public function updateProfile() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json; charset=UTF-8");
    
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    
        $input = json_decode(file_get_contents('php://input'), true);
        error_log("Raw input received for profile update: " . json_encode($input));
    
        $userId = $input['user_id'] ?? '';
        $accessToken = $input['access_token'] ?? '';
        $biography = $input['biography'] ?? '';
        $website = $input['website'] ?? '';
    
        if (empty($userId) || empty($accessToken)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required parameters: user_id and access_token are required"]);
            return;
        }
    
        if (empty($biography) && empty($website)) {
            http_response_code(400);
            echo json_encode(["error" => "At least one field (biography, website) must be provided to update"]);
            return;
        }
    
        try {
            // Construct URL for Instagram Graph API Profile update
            $url = "https://graph.instagram.com/{$userId}";
            
            // Prepare the parameters for the API request
            $params = array_filter([
                'access_token' => $accessToken,
                'biography' => $biography,
                'website' => $website,
            ]);
    
            // Make cURL request to update the Instagram profile
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
    
            error_log("Raw cURL response: " . ($response === false ? "FALSE" : $response));
            error_log("HTTP Code: " . $httpCode);
            if ($curlError) {
                error_log("cURL Error: " . $curlError);
            }
    
            if ($response === false) {
                throw new \Exception("cURL request failed: " . $curlError);
            }
    
            $responseData = json_decode($response, true);
            if ($responseData === null) {
                throw new \Exception("Invalid JSON response from API: " . $response);
            }
    
            // If the API returns an error, capture and log it.
            if (isset($responseData['error'])) {
                $errorMessage = $responseData['error']['message'] ?? "Unknown error from Instagram API";
                throw new \Exception("Instagram API Error: " . $errorMessage);
            }
    
            // Successfully updated
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully',
            ]);
        } catch (\Exception $e) {
            error_log("Error updating profile: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
}
