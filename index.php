<?php
session_start();
require_once __DIR__ . '/src/LoginController.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $loginController = new LoginController();
    $result = $loginController->login($username, $password);
    if ($result === true) {
        // Oturum başlama zamanını kaydet
        $_SESSION['login_time'] = time(); 
        header('Location: task-list.php');  // Giriş başarılıysa yönlendir
        exit();
    } else {
        $message = $result;
    }


}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="login-container">
    <h2>Login</h2>
    <form method="POST" action="">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <?php if ($message): ?>
        <p class="error"><?= $message ?></p>
    <?php endif; ?>
</div>

</body>
</html>
