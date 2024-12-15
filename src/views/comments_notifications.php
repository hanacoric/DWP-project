<?php
global $db;
session_start();
require_once '../includes/db.php';
require_once '../classes/notification.php';
require_once '../classes/user.php';
$userObj = new User($db);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['user_id'];
$notificationObj = new Notification($db);

try {
    $notifications = $notificationObj->getNotificationsForUser($userID);
} catch (PDOException $e) {
    echo "Error fetching notifications: " . $e->getMessage();
    $notifications = [];
}
if (!$userObj->isUserActive($_SESSION['user_id'])) {
    echo "You are blocked and cannot post.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Comment Notifications</title>
    <link rel="stylesheet" href="../../public/assets/css/comments.css">
</head>
<body>
<?php include '../views/sidebar.php'; ?>

<div class="notifications-section">
    <h1>Comment Notifications</h1>

    <ul class="notifications-list">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notification): ?>
                <?php if ($notification['ActionType'] === 'Comment'): ?>
                    <li class="notification-item">
                        <p class="notification-content"><?php echo htmlspecialchars($notification['Content']); ?></p>
                        <small class="notification-timestamp"><?php echo htmlspecialchars($notification['Timestamp']); ?></small>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-notifications">No new notifications.</p>
        <?php endif; ?>
    </ul>
</div>
</body>
</html>

