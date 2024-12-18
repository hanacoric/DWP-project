<?php
global $db;
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/post.php';

$post = new Post($db);

function printResult($testName, $result)
{
    echo $testName . ": " . ($result ? "Passed" : "Failed") . "<br>";
}

$image = "test_image.png";
$caption = "Test caption";
$userID = 3;


$createResult = $post->createPost($image, $caption, $userID);
printResult("Create Post", $createResult);