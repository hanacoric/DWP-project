<?php
// Backend
require_once '../src/includes/db.php';
require_once '../src/classes/auth.php';

$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$auth = new Auth($db);

$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($auth->register($username, $email, $password)) {
        header("Location: login.php?signup=success");
        exit();
    } else {
        $errorMessage = "Username or email already exists.";
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
        <button type="submit" class="button">Sign up</button>
    </form>
    <p>Already have an account? <a href="login.php">Log in</a></p>
    <?php if (!empty($errorMessage)) echo "<p class='error'>$errorMessage</p>"; ?>
</div>
</body>
</html>
