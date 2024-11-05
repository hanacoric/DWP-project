<?php
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/notification.php';

$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$db-> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$notification = new Notification($db);

function printResult($testName, $result)
{
    echo $testName . ": " . ($result ? "Passed" : "Failed") . "<br>";

}

$actionType = "Comment";
$content = "This is a test comment.";
$userID = 1;
$postID = 2;


$createResult = $notification->createNotification($actionType, $content, $userID, $postID);
printResult("Create Notification", $createResult);





