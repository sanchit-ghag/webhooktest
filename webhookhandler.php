<?php
class WebhookHandler {
    private $authToken;
    private $retryAttempts = 3;

    public function __construct($config) {
        $this->authToken = $this->getOAuthToken($config['client_id'], $config['client_secret'], $config['token_url']);
    }

    private function getOAuthToken($clientId, $clientSecret, $tokenUrl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    public function handleIncomingRequest() {
        $headers = getallheaders();
        if (!$this->validateOAuthToken($headers['Authorization'] ?? '')) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $this->sendToThirdParty($data, 'https://third-party.com/api', 'POST', ['Custom-Header' => 'value']);
    }

    private function validateOAuthToken($incomingToken) {
        return $incomingToken === 'Bearer ' . $this->authToken;
    }

    private function sendToThirdParty($data, $url, $method = 'POST', $customHeaders = []) {
        $attempt = 0;
        do {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $headers = [
                'Authorization: Bearer ' . $this->authToken,
                'Content-Type: application/json'
            ];
            foreach ($customHeaders as $key => $value) {
                $headers[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return $response;
            }

            $attempt++;
            sleep(2);
        } while ($attempt < $this->retryAttempts);

        return false;
    }
}
