<?php
global $db;
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['user_id'];

try {
    $stmt = $db->prepare("SELECT Content, Timestamp FROM Notification WHERE UserID = :userId AND ActionType = 'Like' ORDER BY Timestamp DESC");
    $stmt->execute([':userId' => $userID]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching notifications: " . $e->getMessage();
    $notifications = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Likes Notifications</title>
</head>
<body>
<h1>Likes Notifications</h1>
<ul>
    <?php if (!empty($notifications)): ?>
        <?php foreach ($notifications as $notification): ?>
            <li>
                <p><?php echo htmlspecialchars($notification['Content']); ?></p>
                <small><?php echo htmlspecialchars($notification['Timestamp']); ?></small>
            </li>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No new notifications.</p>
    <?php endif; ?>
</ul>
<a href="../../../DWP/public/index.php">Back to Home</a>
</body>
</html>
