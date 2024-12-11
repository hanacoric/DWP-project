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
    <title>Likes Notifications</title>
    <link rel="stylesheet" href="/public/assets/css/home.css">
</head>
<body>
<h1>Like Notifications</h1>

<ul>
    <?php if (!empty($notifications)): ?>
        <?php foreach ($notifications as $notification): ?>
            <?php if ($notification['ActionType'] === 'Like'): ?>
                <li>
                    <p><?php echo htmlspecialchars($notification['Content']); ?></p>
                    <small><?php echo htmlspecialchars($notification['Timestamp']); ?></small>
                    <a href="post.php?post_id=<?php echo $notification['PostID']; ?>">View Post</a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No new notifications.</p>
    <?php endif; ?>
</ul>


<a href="../../../DWP/public/index.php">Back to Home</a>
</body>
</html>
