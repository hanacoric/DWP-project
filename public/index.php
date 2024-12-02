<?php
global $db;
session_start();

require_once '../src/includes/db.php';
require_once '../src/classes/user.php';
require_once '../src/classes/post.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userObj = new User($db);

$userID = $_SESSION['user_id'];
$userProfile = $userObj->getUserProfile($userID);

// handle like/unlike actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['post_id'])) {
    $postID = intval($_POST['post_id']);
    $action = $_POST['action'];

    try {
        if ($action === 'like') {
            $stmt = $db->prepare("INSERT INTO Likes (UserID, PostID) VALUES (:userId, :postId)");
            $stmt->execute([':userId' => $userID, ':postId' => $postID]);
        } elseif ($action === 'unlike') {
            $stmt = $db->prepare("DELETE FROM Likes WHERE UserID = :userId AND PostID = :postId");
            $stmt->execute([':userId' => $userID, ':postId' => $postID]);
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}

// Fetch posts from all users
try {
    $sql = "SELECT Post.PostID, Post.Image, Post.Caption, User.Username FROM Post JOIN User ON Post.UserID = User.UserID ORDER BY Post.UploadDate DESC;";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching posts: " . $e->getMessage();
    $posts = [];
}

//like count for each post
function fetchLikeCount($db, $postID) {
    $stmt = $db->prepare("SELECT COUNT(*) as likeCount FROM Likes WHERE PostID = :postId");
    $stmt->execute(['postId' => $postID]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['likeCount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - RandomShot</title>
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
</head>
<body>
<?php include '../src/views/sidebar.php'; ?>

<div class="main-content">
    <div class="profile-section">
        <img src="<?php echo isset($userProfile['ProfilePicture']) ? htmlspecialchars($userProfile['ProfilePicture']) : 'assets/images/profileicon.png'; ?>" alt="Profile Image" class="profile-image">
        <div class="profile-info">
            <h2 class="username"><?php echo htmlspecialchars($userProfile['Username']); ?></h2>
            <p class="bio"><?php echo htmlspecialchars($userProfile['Bio']); ?></p>
        </div>
    </div>

    <div class="tabs">
        <a href="#" class="active" onclick="showSection('posts')">POSTS</a>
        <a href="#" onclick="showSection('trending')">TRENDING</a>
    </div>

    <section id="posts" class="post-section">
        <div class="post-grid">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post" id="post-<?php echo $post['PostID']; ?>">
                        <img src="<?php echo htmlspecialchars($post['Image']); ?>" alt="Post Image" width="300">
                        <p><?php echo htmlspecialchars($post['Caption']); ?></p>
                        <p>Posted by: <?php echo htmlspecialchars($post['Username']); ?></p>

                        <form method="POST" class="like-form">
                            <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                            <button name="action" value="like" type="submit">Like</button>
                            <button name="action" value="unlike" type="submit">Unlike</button>
                            <span class="like-count"><?php echo fetchLikeCount($db, $post['PostID']); ?> Likes</span>
                        </form>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No posts available.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<script src="assets/js/home.js"></script>
</body>
</html>
