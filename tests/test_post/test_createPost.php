<?php
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/post.php';

$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$post = new Post($db);

function printResult($testName, $result)
{
    echo $testName . ": " . ($result ? "Passed" : "Failed") . "<br>";
}

$image = "test_image.png";
$caption = "Test caption";
$userID = 3; // Use an existing UserID for testing


$createResult = $post->createPost($image, $caption, $userID);
printResult("Create Post", $createResult);