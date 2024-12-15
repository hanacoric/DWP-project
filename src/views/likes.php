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

<div class="likes-section">
    <h1>Users Who Liked This Post</h1>
    <div class="likes-list">
        <?php if (!empty($likes)): ?>
            <?php foreach ($likes as $like): ?>
                <div class="like-item"><?php echo htmlspecialchars($like['Username']); ?></div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-likes">No likes for this post.</p>
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

    .likes-section {
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

    .likes-list {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .like-item {
        background-color: #333;
        padding: 15px;
        border-radius: 10px;
        font-size: 1.2em;
        color: #fff;
        width: 100%;
        max-width: 400px;
        text-align: center;
    }

    .no-likes {
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


