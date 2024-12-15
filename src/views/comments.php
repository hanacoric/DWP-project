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

//fetch recent comments using view
$stmt = $db->prepare("SELECT * FROM RecentComments WHERE PostID = :postId ORDER BY Timestamp DESC");
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
<div class="comments-section">
    <h1>See Who Commented On This Post</h1>
    <div class="comments-list">
        <?php if (!empty($comments)): ?>
            <?php foreach ($comments as $comment): ?>
                <div class="comment-item">
                    <strong><?php echo htmlspecialchars($comment['Username']); ?>:</strong>
                    <?php echo htmlspecialchars($comment['Comment']); ?>
                    <span>(<?php echo $comment['Timestamp']; ?>)</span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-comments">No comments for this post.</p>
        <?php endif; ?>
    </div>
    <form action="../../../DWP/public/index.php">
        <button class="back-home-btn">Go Back Home</button>
    </form>
</div>
</body>
</html>

<style>
    body {
        background-color: #181818;
        color: #fff;
        font-family: sans-serif;
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        text-align: center;
    }

    .comments-section {
        text-align: center;
        padding: 40px;
        background-color: #282828;
        border-radius: 15px;
        width: 80%;
        max-width: 600px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
    }

    h1 {
        color: #FFDF00;
        font-size: 2em;
        margin-bottom: 20px;
    }

    .comments-list {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .comment-item {
        background-color: #333;
        padding: 15px;
        border-radius: 10px;
        font-size: 1.2em;
        color: #fff;
        width: 100%;
        max-width: 400px;
        text-align: center;
    }

    .no-comments {
        font-size: 1.2em;
        margin-top: 20px;
        color: #FFDF00;
    }

    .back-home-btn {
        background-color: #FFDF00;
        color: black;
        border: none;
        padding: 15px 30px;
        border-radius: 10px;
        font-weight: bold;
        font-size: 1.2em;
        cursor: pointer;
        transition: background-color 0.3s ease;
        margin-top: 20px;
        text-align: center;
    }

    .back-home-btn:hover {
        background-color: #FFBF00;
        color: black;
    }

</style>
