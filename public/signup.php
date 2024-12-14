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

// CSRF Token Functions
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $recaptchaToken = $_POST['recaptcha_token'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validate CSRF Token
    if (!validateCsrfToken($csrfToken)) {
        die("CSRF token validation failed.");
    }

    // Verify reCAPTCHA response
    $url = "https://www.google.com/recaptcha/api/siteverify";

    $response = file_get_contents($url . "?secret=" . $secretKey . "&response=" . $recaptchaToken . "&remoteip=" . $_SERVER['REMOTE_ADDR']);

    $captchaResult = json_decode($response, true);

    if (!$captchaResult['success']) {
        $errorMessage = "reCAPTCHA verification failed: " . implode(", ", $captchaResult['error-codes'] ?? []);
    } elseif ($captchaResult['score'] < 0.5) {
        $errorMessage = "reCAPTCHA score too low. Please try again.";
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <h1>Random<span>Shot</span></h1>
    <form action="signup.php" method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" required placeholder="Password">
        <input type="hidden" name="recaptcha_token" id="recaptchaToken">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <button type="submit" class="button">Sign up</button>
    </form>
    <p>Already have an account? <a href="login.php">Log in</a></p>
    <?php if (!empty($errorMessage)) echo "<p class='error'>$errorMessage</p>"; ?>
</div>
</body>
</html>

<script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($siteKey); ?>"></script>
<script>
    grecaptcha.ready(function() {
        grecaptcha.execute('<?php echo htmlspecialchars($siteKey); ?>', {action: 'signup'}).then(function(token) {
            document.getElementById('recaptchaToken').value = token;
        });
    });
</script>
