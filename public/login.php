<?php
session_start();
global $db;

require_once '../src/includes/db.php';
require_once '../src/classes/auth.php';
require_once __DIR__ . '/../load_env.php';

try {
    loadEnv(__DIR__ . '/../.env');
    $secretKey = $_ENV['RECAPTCHA_SECRET_KEY'];
    $siteKey = $_ENV['RECAPTCHA_SITE_KEY'];
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}


function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

$auth = new Auth($db);
$errorMessage = "";
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $recaptchaToken = $_POST['recaptcha_token'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrfToken)) {
        die("CSRF token validation failed.");
    }

    $url = "https://www.google.com/recaptcha/api/siteverify";
    $response = file_get_contents($url . "?secret=" . $secretKey . "&response=" . $recaptchaToken . "&remoteip=" . $_SERVER['REMOTE_ADDR']);
    $captchaResult = json_decode($response, true);

    if (!$captchaResult['success'] || $captchaResult['score'] < 0.5) {
        $errorMessage = "reCAPTCHA verification failed.";
    } else {
        if ($auth->login($username, $password)) {
            header("Location: " . ($auth->isAdmin() ? "admin.php" : "index.php"));
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
    <link rel="stylesheet" href="assets/css/login_signup.css">
</head>
<body>
<div class="container">
    <h1>Random<span>Shot</span></h1>
    <form action="login.php" method="POST">
        <label>
            <input type="text" name="username" placeholder="Username" required>
        </label>
        <label>
            <input type="password" name="password" placeholder="Password" required>
        </label>
        <input type="hidden" name="recaptcha_token" id="recaptcha_token">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <button type="submit" class="button">Log in</button>
    </form>
    <p>Don't have an account? <a href="signup.php">Sign up</a></p>
    <?php if (!empty($errorMessage)) echo "<p class='error'>$errorMessage</p>"; ?>
</div>

<script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($siteKey); ?>"></script>
<script>
    grecaptcha.ready(function() {
        grecaptcha.execute('<?php echo htmlspecialchars($siteKey); ?>', {action: 'login'}).then(function(token) {
            document.getElementById('recaptcha_token').value = token;
        });
    });
</script>
</body>
</html>
