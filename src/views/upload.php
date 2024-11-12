<?php
require_once '../includes/db.php';
require_once '../classes/post.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /DWP/public/login.php");
    exit();
}

$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$post = new Post($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = htmlspecialchars($_POST['caption']);
    $imageUrl = trim($_POST['image_url']);
    $userId = $_SESSION['user_id'];

    // Validate URL format
    if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        if ($post->createPost($imageUrl, $caption, $userId)) {
            header("Location: /DWP/public/index.php");
            exit();
        } else {
            echo "Error: Unable to save the post in the database.";
        }
    } else {
        echo "Error: Please enter a valid URL.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Post - RandomShot</title>
    <link rel="stylesheet" href="../../../DWP/public/assets/css/upload.css">
</head>
<body>
<?php include '../views/sidebar.php'; ?>

<div class="upload-container">
    <h2>Create New Post</h2>
    <form action="upload.php" method="POST">
        <label for="image_url">Image URL:</label>
        <input type="url" name="image_url" id="image_url" placeholder="Enter direct image URL" required>

        <label for="caption">Caption:</label>
        <textarea name="caption" id="caption" rows="3" placeholder="Write your caption..."></textarea>

        <button type="submit" name="submit">Share Post</button>
    </form>
</div>

</body>
</html>

