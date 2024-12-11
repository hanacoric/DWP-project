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
</div>
</body>
</html>
