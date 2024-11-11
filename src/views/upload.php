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
    $image = $_FILES['image'];
    $userId = $_SESSION['user_ id'];

    if ($image['error'] === UPLOAD_ERR_OK) {
        $targetDirectory = "../assets/images/Post/";
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0777, true); // Create directory if it doesn't exist
        }

        $fileName = basename($image['name']);
        $targetFilePath = $targetDirectory . $fileName;

        if (move_uploaded_file($image['tmp_name'], $targetFilePath)) {
            $imagePath = "assets/images/Post/" . $fileName;

            if ($post->createPost($imagePath, $caption, $userId)) {
                echo "Post shared successfully!";
                header("Location: /DWP/public/index.php");
                exit();
            } else {
                echo "Error: Unable to save the post in the database.";
            }
        } else {
            echo "Error: Unable to upload the image file.";
        }
    } else {
        echo "Error: Please select a valid image file.";
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
    <form action="upload.php" method="POST" enctype="multipart/form-data">
        <button type="submit" name="submit">Select image</button>
        <input type="file" name="image" id="image" required>

        <label for="caption">Caption:</label>
        <textarea name="caption" id="caption" rows="3" placeholder="Write your caption..."></textarea>

        <button type="submit" name="submit">Share Post</button>
    </form>
</div>

<script src="../../../DWP/public/assets/js/upload.js"></script>
</body>
</html>


