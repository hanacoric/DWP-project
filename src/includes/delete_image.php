<?php
require_once 'src/includes/db.php';
if (!isset($db)) {
    die("Database connection not found.");
}

if (isset($_POST['delete'])) {
    $imageId = $_POST['image_id'];
    $imagePath = $_POST['image_path'];

    if (file_exists($imagePath)) {
        unlink($imagePath);
    }

    $sql = "DELETE FROM Post WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $imageId);

    if ($stmt->execute()) {
        echo "Image deleted successfully!";
    } else {
        echo "Failed to delete image from database.";
    }
}

