<?php
global $db;
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['user_id'];

// Fetch notifications for comments
$stmt = $db->prepare("SELECT Message, Timestamp FROM Notifications WHERE UserID = :userId AND ActionType = 'Comment' ORDER BY Timestamp DESC");
$stmt->execute([':userId' => $userID]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Comments Notifications</title>
</head>
<body>
<h1>Notifications - Comments</h1>
<ul>
    <?php if (!empty($notifications)): ?>
        <?php foreach ($notifications as $notification): ?>
            <li>
                <p><?php echo htmlspecialchars($notification['Message']); ?></p>
                <small><?php echo htmlspecialchars($notification['Timestamp']); ?></small>
            </li>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No notifications found.</p>
    <?php endif; ?>
</ul>
<a href="../../../DWP/public/index.php">Back to Home</a>
</body>
</html>
