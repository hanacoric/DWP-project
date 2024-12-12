<?php
global $db;
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /DWP/public/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = htmlspecialchars($_POST['caption']);
    $userId = $_SESSION['user_id'];

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $imageData = file_get_contents($_FILES['image_file']['tmp_name']);

        try {
            $sql = "INSERT INTO Post (BlobImage, Caption, UploadDate, UserID) VALUES (:blobImage, :caption, NOW(), :userID)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':blobImage', $imageData, PDO::PARAM_LOB);
            $stmt->bindParam(':caption', $caption, PDO::PARAM_STR);
            $stmt->bindParam(':userID', $userId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                header("Location: /DWP/public/index.php");
                exit();
            } else {
                echo "Error: Unable to save the post.";
            }
        } catch (PDOException $e) {
            echo "Database error: " . $e->getMessage();
        }
    } else {
        echo "Error: Please upload a valid image.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Post</title>
    <link rel="stylesheet" href="../../../DWP/public/assets/css/upload.css">
</head>
<body>
<?php include '../views/sidebar.php'; ?>

<div class="upload-container">
    <h2>Create New Post</h2>
    <form action="upload.php" method="POST" enctype="multipart/form-data">
        <label for="image_file">Upload Image:</label>
        <input type="file" name="image_file" id="image_file" accept="image/*" required>

        <label for="caption">Caption:</label>
        <textarea name="caption" id="caption" rows="3" placeholder="Write your caption..."></textarea>

        <button type="submit">Share Post</button>
    </form>
</div>
</body>
</html>
