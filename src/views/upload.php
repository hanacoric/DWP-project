<?php
global $db;
session_start();
require_once '../includes/db.php';

// Generate CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /DWP/public/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    $caption = htmlspecialchars($_POST['caption'] ?? '', ENT_QUOTES, 'UTF-8');
    $userId = $_SESSION['user_id'];

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image_file'];
        $maxFileSize = 5242880; // 5MB
        $minFileSize = 10240;   // 10KB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if ($file['size'] < $minFileSize) {
            echo "Error: The uploaded file is too small. Minimum size is 10KB.";
            exit();
        }
        if ($file['size'] > $maxFileSize) {
            echo "Error: The uploaded file is too large. Maximum size is 5MB.";
            exit();
        }

        if (!in_array($file['type'], $allowedTypes)) {
            echo "Error: Only JPG, PNG, and GIF formats are allowed.";
            exit();
        }

        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            echo "Error: The uploaded file is not a valid image.";
            exit();
        }
        list($width, $height) = $imageInfo;
        if ($width > 1080 || $height > 1080) {
            echo "Error: Image dimensions are too big. Maximum size is 1080x1080 pixels.";
            exit();
        }
        if ($width < 100 || $height < 100) {
            echo "Error: Image dimensions are too small. Minimum size is 100x100 pixels.";
            exit();
        }

        $imageData = file_get_contents($file['tmp_name']);

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
    <link rel="stylesheet" href="/public/assets/css/upload.css">
</head>
<body>
<?php include '../views/sidebar.php'; ?>

<div class="upload-container">
    <h2>Create New Post</h2>
    <form action="upload.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <label for="image_file">Upload Image:</label>
        <input type="file" name="image_file" id="image_file" accept="image/*" required>

        <label for="caption">Caption:</label>
        <textarea name="caption" id="caption" rows="3" placeholder="Write your caption..." maxlength="100" required></textarea>
        <small id="charCount">0 / 100</small>

        <button type="submit">Share Post</button>
    </form>
</div>

<script src="../../../DWP/public/assets/js/upload.js"></script>
</body>
</html>
