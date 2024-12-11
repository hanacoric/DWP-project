<?php
global $db;
require_once '../includes/db.php';
require_once '../classes/notification.php';
require_once '../classes/user.php';
$userObj = new User($db);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$postID = intval($_GET['post_id']);

// Fetch all comments for the post
$stmt = $db->prepare("SELECT Comments.CommentID, Comments.Comment, Comments.Timestamp, User.Username, Comments.UserID FROM Comments JOIN User ON Comments.UserID = User.UserID WHERE Comments.PostID = :postId ORDER BY Comments.Timestamp ASC");
$stmt->execute(['postId' => $postID]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$userObj->isUserActive($_SESSION['user_id'])) {
    echo "You are blocked and cannot post.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Comments for Post #<?php echo $postID; ?></title>
</head>
<body>
    <h1>Comments for Post #<?php echo $postID; ?></h1>
    <ul>
        <?php foreach ($comments as $comment): ?>
            <li>
                <strong><?php echo htmlspecialchars($comment['Username']); ?>:</strong>
                <?php echo htmlspecialchars($comment['Comment']); ?>
                <span>(<?php echo $comment['Timestamp']; ?>)</span>
            </li>
        <?php endforeach; ?>
    </ul>
    <a href="../../../DWP/public/index.php">Back to Home</a>
</body>
</html>

