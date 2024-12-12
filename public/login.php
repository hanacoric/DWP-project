<?php
global $db;
session_start();
require_once '../src/includes/db.php';
require_once '../src/classes/auth.php';


$auth = new Auth($db);

$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Attempt login
    if ($auth->login($username, $password)) {
        if ($auth->isAdmin()) {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $errorMessage = "Invalid username or password.";
    }
}


// reCAPTCHA verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $secretKey = "6LehAJoqAAAAAJ-XeGtXukQyskqTdpMVVs7CEC6y"; // Replace with your Secret Key
    $recaptchaToken = $_POST['recaptcha_token'];
    $remoteIp = $_SERVER['REMOTE_ADDR'];

    // Verify the token using Google's API
    $url = "https://www.google.com/recaptcha/api/siteverify";
    $data = [
        'secret' => $secretKey,
        'response' => $recaptchaToken,
        'remoteip' => $remoteIp
    ];

    // Use cURL to make the POST request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $captchaResult = json_decode($response, true);

    // Check reCAPTCHA score
    if ($captchaResult['success'] && $captchaResult['score'] >= 0.5) {
        echo "reCAPTCHA verification successful!";
    } else {
        echo "reCAPTCHA verification failed. Please try again.";
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RandomShot - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <h1>Random<span>Shot</span></h1>
    <form action="login.php" method="POST">
        <label>
            <input type="text" name="username" placeholder="Username" required>
        </label>
        <label>
            <input type="password" name="password" required placeholder="Password">
        </label>
        <button type="submit" class="button">Log in</button>
    </form>
    <p>Don't have an account? <a href="signup.php">Sign up</a></p>
    <?php if (!empty($errorMessage)) echo "<p class='error'>$errorMessage</p>"; ?>

    <input type="hidden" name="recaptcha_token" id="recaptchaToken">
</div>
</body>
</html>

<!-- Include the reCAPTCHA API -->
<script src="https://www.google.com/recaptcha/api.js?render=6LehAJoqAAAAANKMmswjqAzrgc2s35k7aM03SeMy"></script>
<script>
    grecaptcha.ready(function () {
        grecaptcha.execute('6LehAJoqAAAAANKMmswjqAzrgc2s35k7aM03SeMy', { action: 'login' }).then(function (token) {
            document.getElementById('recaptchaToken').value = token;
        });
    });
</script>
