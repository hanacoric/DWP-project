<?php
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/post.php';

$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$post = new Post($db);

$image = "test_image.png";
$caption = "Test caption";
$userID = 3;

$post->createPost($image, $caption, $userID);

$postID = $db->lastInsertId();


echo "Testing getPost:<br>";
$retrievedPost = $post->getPost($postID);

if ($retrievedPost) {
    echo "Post retrieved successfully.<br>";
    echo "Image: " . htmlspecialchars($retrievedPost['Image']) . "<br>";
    echo "Caption: " . htmlspecialchars($retrievedPost['Caption']) . "<br>";
} else {
    echo "Failed to retrieve post.<br>";
}

