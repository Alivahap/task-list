<?php
class LoginController {
    private $apiUrl = 'https://api.baubuddy.de/index.php/login';
    private $authorization = 'Authorization: Basic QVBJX0V4cGxvcmVyOjEyMzQ1NmlzQUxhbWVQYXNz';

    public function login($username, $password) {
        // API'ye POST isteği atmak için cURL kullanıyoruz
        $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $password = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
        $data = array(
            "username" => "".$username,
            "password" => "".$password
        );

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($this->authorization, 'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));


        //if there are curl error in your localhost. it disabled secure connect.
        //this code optional and its not recomended.I wrote it just in case
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        // cURL hatalarını kontrol edelim
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return 'cURL Error: ' . $error_msg;
        }

        curl_close($ch);
        $result = json_decode($response, true);
        if (isset($result['oauth']['access_token'])) {
            $_SESSION['token'] = $result['oauth']['access_token']; // Bearer Token'ı sakla
            echo $_SESSION['refresh_token'] = $result['oauth']['refresh_token'];
         
         

            $_SESSION['username'] = $username; 
            $_SESSION['password'] = $password; 

            header('Location: tasklist.php');
            exit();
        } else {
            return isset($result['error']) ? 'API Error: ' . $result['error'] : 'Login failed. Please try again.';
        }
    }
       
    }

