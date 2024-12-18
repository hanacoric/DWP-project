<?php
global $db;
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/post.php';

function printResult($testName, $result)
{
    echo $testName . ": " . ($result ? "Passed" : "Failed") . "<br>";
}
$post = new Post($db);

$image = "test_image.png";
$caption = "Original caption";
$userID = 3;

$post->createPost($image, $caption, $userID);
$postID = $db->lastInsertId();

$newCaption = "Updated caption";
$updateResult = $post->updatePost($postID, $newCaption);

echo "Testing updatePost: ";
printResult("Update Post", $updateResult);

$updatedPost = $post->getPost($postID);

if ($updatedPost && $updatedPost['Caption'] === $newCaption) {
    echo "Caption updated<br>";
} else {
    echo "Error updating caption<br>";
}

