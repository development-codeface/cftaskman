<?php

namespace App\Services;
use App\Models\DeviceToken;
use Exception;

class FirebaseNotificationService {

    private $projectId;
    private $accessToken;
    private $credentialsFile;

    public function __construct() {
        $this->credentialsFile = storage_path('app/firebase/firebase-service-account.json');
        $this->loadCredentials();
    }

    private function loadCredentials() {
        if (!file_exists($this->credentialsFile)) {
            throw new Exception("Firebase credentials file not found: " . $this->credentialsFile);
        }

        $credentials = json_decode(file_get_contents($this->credentialsFile), true);
        $this->projectId = $credentials['project_id'];
        $this->getAccessToken($credentials);
    }

    private function getAccessToken($credentials) {
        $key = $credentials['private_key'];
        $email = $credentials['client_email'];

        $payload = [
            'iss' => $email,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => time(),
            'exp' => time() + 3600
        ];

        $jwt = $this->createJWT($key, $payload);
        $this->accessToken = $this->requestAccessToken($jwt);
    }

    private function createJWT($privateKey, $payload) {
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $payload = json_encode($payload);

        $headerEncoded = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $payloadEncoded = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $signatureInput = $headerEncoded . "." . $payloadEncoded;

        $signature = '';
        openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signatureEncoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $signatureInput . "." . $signatureEncoded;
    }

    private function requestAccessToken($jwt) {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=' . $jwt
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (!isset($data['access_token'])) {
            throw new Exception("Failed to get access token from Firebase");
        }

        return $data['access_token'];
    }

    public function sendToToken($token, $title, $body, $data = []) {
        $message = [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body
            ],
            'data' => $data
        ];

        return $this->sendMessage($message);
    }

    public function sendMessage($message) {
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->accessToken
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['message' => $message]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return ['success' => true];
        } else {
            $error = json_decode($response, true);
            return [
                'success' => false,
                'error' => $error['error']['message'] ?? 'Unknown error'
            ];
        }
    }


public function sendToUser($userId, $title, $body, $data = [])
{
   
    
    // Get all device tokens for this user
    $tokens = DeviceToken::where('user_id', $userId)
        ->pluck('fcm_token')
        ->toArray();

    if (empty($tokens)) {
        return ['success' => false, 'message' => 'No FCM tokens found for this user'];
    }

    // Send notification to each token
    foreach ($tokens as $token) {
        $this->sendToToken($token, $title, $body, $data);
    }

    return ['success' => true];
}

}
