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
    $sql = "SELECT Post.PostID, Post.Caption, User.Username AS LikedBy FROM Post  LEFT JOIN Likes ON Post.PostID = Likes.PostID  LEFT JOIN User ON Likes.UserID = User.UserID  WHERE Post.UserID = :userId  ORDER BY Post.UploadDate DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([':userId' => $userID]);
    $likedPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching likes: " . $e->getMessage();
    $likedPosts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Likes on Your Posts</title>
    <link rel="stylesheet" href="../../public/assets/css/home.css">
</head>
<body>
<h1>Likes on Your Posts</h1>

<?php if (!empty($likedPosts)): ?>
    <?php
    $currentPostID = null;
    foreach ($likedPosts as $like):
        if ($currentPostID !== $like['PostID']):
            if ($currentPostID !== null): ?>
                </ul>
            <?php endif;
            $currentPostID = $like['PostID']; ?>
            <h3><?php echo htmlspecialchars($like['Caption']); ?></h3>
            <ul>
        <?php endif; ?>

        <li><?php echo $like['LikedBy'] !== null ? htmlspecialchars($like['LikedBy']) : "No likes yet"; ?></li>

    <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>You have no posts with likes yet.</p>
<?php endif; ?>

<a href="../../../DWP/public/index.php">Back to Home</a>
</body>
</html>

