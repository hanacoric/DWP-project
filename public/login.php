<?php
global $db;
require_once '../src/includes/db.php';
require_once '../src/classes/auth.php';

// Load environment variables from .env
function loadEnv($filePath = __DIR__ . '/../.env') {
    if (!file_exists($filePath)) {
        throw new Exception("Environment file not found: $filePath");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

try {
    loadEnv();
    $secretKey = $_ENV['RECAPTCHA_SECRET_KEY'];
    $siteKey = $_ENV['RECAPTCHA_SITE_KEY'];
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

session_start();

$auth = new Auth($db);

$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $recaptchaToken = $_POST['recaptcha_token'] ?? '';

    // Verify reCAPTCHA response
    $url = "https://www.google.com/recaptcha/api/siteverify";

    $response = file_get_contents($url . "?secret=" . $secretKey . "&response=" . $recaptchaToken . "&remoteip=" . $_SERVER['REMOTE_ADDR']);

    $captchaResult = json_decode($response, true);

    if (!$captchaResult['success']) {
        $errorMessage = "reCAPTCHA verification failed: " . implode(", ", $captchaResult['error-codes'] ?? []);
    } elseif ($captchaResult['score'] < 0.5) {
        $errorMessage = "reCAPTCHA score too low. Please try again.";
    } else {
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
        <input type="hidden" name="recaptcha_token" id="recaptcha_token">
        <button type="submit" class="button">Log in</button>
    </form>
    <p>Don't have an account? <a href="signup.php">Sign up</a></p>
    <?php if (!empty($errorMessage)) echo "<p class='error'>$errorMessage</p>"; ?>
</div>
</body>
</html>

<script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($siteKey); ?>"></script>
<script>
    grecaptcha.ready(function() {
        grecaptcha.execute('<?php echo htmlspecialchars($siteKey); ?>', {action: 'login'}).then(function(token) {
            document.getElementById('recaptcha_token').value = token;
        });
    });
</script>
