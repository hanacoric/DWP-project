<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

session_start();

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !is_string($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

$auth = new Auth($db);
$errorMessage = "";
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
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
        if ($auth->register($username, $email, $password)) {
            header("Location: login.php?signup=success");
            exit();
        } else {
            $errorMessage = "Username or email already exists.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RandomShot - Sign Up</title>
    <link rel="stylesheet" href="assets/css/login_signup.css">
</head>
<body>
<div class="container">
    <h1>Random<span>Shot</span></h1>
    <form action="signup.php" method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" required placeholder="Password">
        <input type="hidden" name="recaptcha_token" id="recaptchaToken">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <button type="submit" class="button">Sign up</button>
    </form>
    <p>Already have an account? <a href="login.php">Log in</a></p>
    <?php if (!empty($errorMessage)) echo "<p class='error'>$errorMessage</p>"; ?>
</div>

<script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($siteKey); ?>"></script>
<script>
    grecaptcha.ready(function() {
        grecaptcha.execute('<?php echo htmlspecialchars($siteKey); ?>', {action: 'signup'}).then(function(token) {
            document.getElementById('recaptchaToken').value = token;
        });
    });
</script>
</body>
</html>
