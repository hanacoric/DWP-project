<?php
global $db;
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/Notification.php';

function printResult($testName, $result) {
    echo $testName . ": " . ($result ? "Passed" : "Failed") . "<br>";
}



$notification = new Notification($db);

$actionType = "Comment";
$content = "This is a test comment for deletion.";
$userID = 1;
$postID = 2;

echo "Creating a test comment...<br>";
$createResult = $notification->createNotification($actionType, $content, $userID, $postID);

if ($createResult) {
    echo "Comment notification created.<br>";

    $notificationID = $db->lastInsertId();

    echo "Attempting to delete the comment...<br>";
    $deleteResult = $notification->deleteComment($notificationID, $userID);
    printResult("Delete Comment", $deleteResult);

    $verifySql = "SELECT * FROM Notification WHERE NotificationID = :notificationID";
    $verifyStmt = $db->prepare($verifySql);
    $verifyStmt->bindParam(':notificationID', $notificationID);
    $verifyStmt->execute();
    $deletedComment = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$deletedComment) {
        echo "Verification: Comment deleted.<br>";
    } else {
        echo "Verification failed: Comment still exists.<br>";
    }
} else {
    echo "Failed to create test comment notification. Test cannot proceed.<br>";
}
