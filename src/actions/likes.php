<?php
global $db;
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../classes/auth.php';

//checks if a user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

//fetches likes for the post
$stmt = $db->prepare("SELECT u.Username FROM Likes l JOIN User u ON l.UserID = u.UserID WHERE l.PostID = :postId");
$stmt->execute(['postId' => $postId]);
$likes = $stmt->fetchAll(PDO::FETCH_ASSOC);

//handles like and unlike
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset ($_POST['action'])) {
    if ($_POST['action'] === 'like') {
        $stmt = $db->prepare("INSERT INTO Likes (UserID, PostID) VALUES (:userId, :postId)");
        $stmt->execute([':userId' => $userId, ':postId' => $postId]);
    } elseif ($_POST['action'] === 'unlike') {
        $stmt = $db->prepare("DELETE FROM Likes WHERE UserID = :userId AND PostID = :postId");
        $stmt->execute([':userId' => $userId, ':postId' => $postId]);
    }
    header("Location: likes.php?post_id=$postId");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Likes</title>
</head>
<body>
<h1>Likes for Post #<?php echo $postId; ?></h1>

<ul>
    <?php foreach ($likes as $like): ?>
        <li><?php echo htmlspecialchars($like['Username']); ?> liked this post.</li>
    <?php endforeach; ?>
</ul>

<form method="POST">
    <button name="action" value="like">Like</button>
    <button name="action" value="unlike">Unlike</button>
</form>

<a href="../../../DWP/public/index.php">Back to Home</a>
</body>
</html>
