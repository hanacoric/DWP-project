<?php
global $db;
session_start();
require_once '../includes/db.php';
require_once '../classes/user.php';
$userObj = new User($db);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['user_id'];
$postID = intval(isset($_GET['post_id']) ? $_GET['post_id'] : 0);

try {
    $stmt = $db->prepare("SELECT User.Username FROM Likes JOIN User ON Likes.UserID = User.UserID WHERE Likes.PostID = :postId");
    $stmt->execute([':postId' => $postID]);
    $likes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching likes: " . $e->getMessage();
    $likes = [];
}

if (!$userObj->isUserActive($_SESSION['user_id'])) {
    echo "You are blocked and cannot like posts.";
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Likes</title>
</head>
<body>
<h1>Users Who Liked This Post</h1>
<ul>
    <?php if (!empty($likes)): ?>
        <?php foreach ($likes as $like): ?>
            <li><?php echo htmlspecialchars($like['Username']); ?></li>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No likes for this post.</p>
    <?php endif; ?>
</ul>

<a href="../../../DWP/public/index.php">Back to Home</a>
</body>
</html>

