<?php
global $db;
require_once '../includes/db.php';
require_once '../classes/user.php';
require_once '../classes/user.php';
$userObj = new User($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Path to store uploaded image
    $targetDir = "assets/images/";
    $targetFile = $targetDir . basename($_FILES["fileInput"]["name"]);
    $caption = $_POST['caption'];

    // Move the uploaded file to target directory
    if (move_uploaded_file($_FILES["fileInput"]["tmp_name"], $targetFile)) {
        $db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");

        // Insert into Post table
        $sql = "INSERT INTO Post (Image, Caption, UploadDate, UserID) VALUES (:image, :caption, NOW(), :userID)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':image', $targetFile);
        $stmt->bindParam(':caption', $caption);
        $stmt->bindParam(':userID', $_SESSION['user_id']);
        $stmt->execute();

        header("Location: home.php");
        exit();
    } else {
        echo "Error uploading file.";
    }
}

if (!$userObj->isUserActive($_SESSION['user_id'])) {
    echo "You are blocked and cannot post.";
    exit();
}


