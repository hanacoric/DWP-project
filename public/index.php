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

// handle like/unlike actions and add and delete comments
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
        }elseif ($action === 'comment') {
            $comment = trim($_POST['comment']);
            if (!empty($comment)) {
                $stmt = $db->prepare("INSERT INTO Comments (UserID, PostID, Comment) VALUES (:userId, :postId, :comment)");
                $stmt->execute([':userId' => $userID, ':postId' => $postID, ':comment' => $comment]);
            }
        } elseif ($action === 'delete_comment') {
            $commentID = intval($_POST['comment_id']);
            $stmt = $db->prepare("DELETE FROM Comments WHERE CommentID = :commentId AND UserID = :userId");
            $stmt->execute([':commentId' => $commentID, ':userId' => $userID]);
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

// Fetch comments for each post
function fetchComments($db, $postID, $limit = 5) {
    $stmt = $db->prepare("SELECT Comments.CommentID, Comments.Comment, Comments.Timestamp, User.Username, Comments.UserID FROM Comments JOIN User ON Comments.UserID = User.UserID WHERE Comments.PostID = :postId ORDER BY Comments.Timestamp ASC LIMIT :commentLimit");
    $stmt->bindParam(':postId', $postID, PDO::PARAM_INT);
    $stmt->bindParam(':commentLimit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <form method="POST" class="comment-form">
                            <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                            <label>
                                <textarea name="comment" placeholder="Write a comment..." required></textarea>
                            </label>
                            <button name="action" value="comment" type="submit">Post Comment</button>
                        </form>

                        <h3>Comments</h3>
                        <ul>
                            <?php
                            $comments = fetchComments($db, $post['PostID'], 3);
                            foreach ($comments as $comment): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($comment['Username']); ?>:</strong>
                                    <?php echo htmlspecialchars($comment['Comment']); ?>
                                    <span>(<?php echo $comment['Timestamp']; ?>)</span>
                                    <?php if ($comment['UserID'] == $userID): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['CommentID']; ?>">
                                            <button name="action" value="delete_comment" type="submit">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <a href="../src/views/comments.php?post_id=<?php echo $post['PostID'];?>">View All Comments</a>
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
