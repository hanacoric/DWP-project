<?php
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/post.php';

function printResult($testName, $result)
{
    echo $testName . ": " . ($result ? "Passed" : "Failed") . "<br>";
}

$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$post = new Post($db);

$image = "test_image.png";
$caption = "Caption to be deleted";
$userID = 3;

$post->createPost($image, $caption, $userID);
$postID = $db->lastInsertId();

$deleteResult = $post->deletePost($postID);
printResult("Delete Post", $deleteResult);

$deletedPost = $post->getPost($postID);

if (!$deletedPost) {
    echo "Post deleted.<br>";
} else {
    echo "Error deleting post.<br>";
}
