<?php
global $db;
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/notification.php';


$notification = new Notification($db);

function printResult($testName, $result)
{
    echo $testName . ": " . ($result ? "Passed" : "Failed") . "<br>";

}

$notificationID = 3;
$newContent = "LMAO.";
$userID = 1;

$updateResult = $notification->updateComment($notificationID, $newContent, $userID);
printResult("Update Comment", $updateResult);

$updatedNotification = $notification->getNotificationsForUser($userID);

if ($updatedNotification) {
    foreach ($updatedNotification as $notif) {
        if ($notif['NotificationID'] == $notificationID) {
            echo "Updated comment: " . htmlspecialchars($notif['Content']) . "<br>";
        }
    }
}
