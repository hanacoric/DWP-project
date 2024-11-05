<?php
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/Notification.php';

$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$notification = new Notification($db);

$userID = 1;

echo "Testing getNotificationsForUser:<br>";
$notifications = $notification->getNotificationsForUser($userID);

if ($notifications) {
    echo "Notifications retrieved:<br>";
    foreach ($notifications as $notif) {
        // Check if Content is NULL and handle it appropriately
        $action = htmlspecialchars($notif['ActionType']);
        $content = isset($notif['Content']) ? htmlspecialchars($notif['Content']) : "(No content)";

        echo "Notification ID: " . $notif['NotificationID'] . " | Action: " . $action . " | Content: " . $content . "<br>";
    }
} else {
    echo "Failed to retrieve notifications.";
}
