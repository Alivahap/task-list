<?php
class SessionManager {
    private $sessionDuration = 1800; // 30 minutes

    public function startSession() {
        session_start();
        if (!isset($_SESSION['token'])) {
            header('Location: index.php');
            exit();
        }
    }

    public function checkSession() {
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $this->sessionDuration)) {
            $this->destroySession();
        } else {
            $_SESSION['login_time'] = time();  // Update session time
        }
    }

    public function destroySession() {
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit();
    }
}

class TokenManager {
    private const TOKEN_FILE = 'tokens.json';

    public function __construct() {
        if (file_exists(self::TOKEN_FILE)) {
            chmod(self::TOKEN_FILE, 0600);
        }
    }

    public function updateTokens() {
        $curl = curl_init();
        $data = array(
            "username" => $_SESSION['username'],
            "password" => $_SESSION['password']
        );

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.baubuddy.de/index.php/login",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic QVBJX0V4cGxvcmVyOjEyMzQ1NmlzQUxhbWVQYXNz",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => false, // Optional for local testing
            CURLOPT_SSL_VERIFYHOST => false  // Optional for local testing
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return false;
        }

        $data = json_decode($response, true);
        if (isset($data['oauth']['access_token']) && isset($data['oauth']['refresh_token'])) {
            file_put_contents(self::TOKEN_FILE, json_encode($data));
            return $data;
        } else {
            echo "Failed to get tokens. Check file permissions.";
            return false;
        }
    }

    public function getAccessToken() {
        if (file_exists(self::TOKEN_FILE)) {
            $data = json_decode(file_get_contents(self::TOKEN_FILE), true);
            $lastUpdate = $data['last_update'] ?? 0;
            $currentTime = time();

            if ($currentTime - $lastUpdate > 3600) {
                $data = $this->updateTokens();
                if ($data) {
                    $data['last_update'] = $currentTime;
                    file_put_contents(self::TOKEN_FILE, json_encode($data));
                }
            }

            return $data['oauth']['access_token'] ?? null;
        } else {
            $data = $this->updateTokens();
            if ($data) {
                $data['last_update'] = time();
                file_put_contents(self::TOKEN_FILE, json_encode($data));
                return $data['oauth']['access_token'];
            }
            return null;
        }
    }
}

class ApiRequestManager {
    private $tokenManager;

    public function __construct(TokenManager $tokenManager) {
        $this->tokenManager = $tokenManager;
    }

    public function makeApiRequest($url) {
        $apiToken = $this->tokenManager->getAccessToken();
        if (!$apiToken) {
            die("Access Token could not be obtained");
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $apiToken",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Optional for local testing
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Optional for local testing

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            echo "An error occurred while retrieving data from the API.";
        } else {
            $data = json_decode($response, true);

            if (isset($data['error']['code'])) {
                $this->tokenManager->updateTokens();

                $apiToken = $this->tokenManager->getAccessToken();
                if (!$apiToken) {
                    die("Access token alınamadı.");
                }

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $apiToken",
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Optional for local testing
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Optional for local testing

                $response = curl_exec($ch);
                $err = curl_error($ch);
                curl_close($ch);

                return $response;
            } else {
                return $response;
            }
        }
    }
}
