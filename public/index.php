<?php
global $db;
session_start();

require_once '../src/includes/db.php';
require_once '../src/classes/user.php';
require_once '../src/classes/post.php';
require_once '../src/classes/notification.php';
require_once '../src/classes/auth.php';

$auth = new Auth($db);

if ($auth->isAdmin()) {
    header("Location: admin.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['user_id'];
$userObj = new User($db);
$notificationObj = new Notification($db);
$userProfile = $userObj->getUserProfile($userID);

function fetchLikeCount($db, $postID) {
    $stmt = $db->prepare("SELECT COUNT(*) as likeCount FROM Likes WHERE PostID = :postId");
    $stmt->execute(['postId' => $postID]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['likeCount'];
}

function fetchComments($db, $postID, $limit = 3) {
    $stmt = $db->prepare("SELECT Comments.CommentID, Comments.Comment, Comments.Timestamp, User.Username, Comments.UserID FROM Comments JOIN User ON Comments.UserID = User.UserID WHERE Comments.PostID = :postId ORDER BY Comments.Timestamp ASC LIMIT :commentLimit");
    $stmt->bindParam(':postId', $postID, PDO::PARAM_INT);
    $stmt->bindParam(':commentLimit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['action'])) {
    $postID = intval($_POST['post_id']);
    $action = $_POST['action'];

    try {
     if ($action === 'like') {
    $stmt = $db->prepare("SELECT COUNT(*) as likeExists FROM Likes WHERE UserID = :userId AND PostID = :postId");
    $stmt->execute([':userId' => $userID, ':postId' => $postID]);
    $likeExists = $stmt->fetch(PDO::FETCH_ASSOC)['likeExists'];

    if (!$likeExists) {
        $stmt = $db->prepare("INSERT INTO Likes (UserID, PostID) VALUES (:userId, :postId)");
        $stmt->execute([':userId' => $userID, ':postId' => $postID]);

        $stmt = $db->prepare("SELECT Username FROM User WHERE UserID = :userId");
        $stmt->execute([':userId' => $userID]);
        $likingUsername = $stmt->fetch(PDO::FETCH_ASSOC)['Username'];

        $stmt = $db->prepare("SELECT UserID FROM Post WHERE PostID = :postId");
        $stmt->execute([':postId' => $postID]);
        $postOwner = $stmt->fetch(PDO::FETCH_ASSOC)['UserID'];

        if ($postOwner && $postOwner != $userID) {
            $notificationObj->createNotification(
                'Like',
                "$likingUsername liked your post.",
                $postOwner,
                $postID
            );
        }

        $notificationObj->createNotification(
            'Like',
            "You liked a post.",
            $userID,
            $postID
        );
    }

        } elseif ($action === 'unlike') {
            $stmt = $db->prepare("DELETE FROM Likes WHERE UserID = :userId AND PostID = :postId");
            $stmt->execute([':userId' => $userID, ':postId' => $postID]);
     } elseif ($action === 'comment') {
         $comment = trim($_POST['comment']);
         if (!empty($comment)) {
             $db->beginTransaction();
             try {
                 $stmt = $db->prepare("INSERT INTO Comments (UserID, PostID, Comment) VALUES (:userId, :postId, :comment)");
                 $stmt->execute([':userId' => $userID, ':postId' => $postID, ':comment' => $comment]);

                 $stmt = $db->prepare("SELECT User.Username AS CommentingUser, Post.UserID AS PostOwner FROM User JOIN Post ON Post.PostID = :postId WHERE User.UserID = :userId");
                 $stmt->execute([':userId' => $userID, ':postId' => $postID]);
                 $data = $stmt->fetch(PDO::FETCH_ASSOC);

                 if ($data && $data['PostOwner'] && $data['PostOwner'] != $userID) {
                     $notificationObj->createNotification(
                         'Comment',
                         "{$data['CommentingUser']} commented on your post.",
                         $data['PostOwner'],
                         $postID
                     );
                 }

                 $notificationObj->createNotification(
                     'Comment',
                     "You commented on a post.",
                     $userID,
                     $postID
                 );

                 $db->commit();
             } catch (PDOException $e) {
                 $db->rollBack();
                 echo "Error: " . $e->getMessage();
             }
         }
     } elseif ($action === 'delete_comment') {
         $commentID = intval($_POST['comment_id']);
         $stmt = $db->prepare("SELECT UserID FROM Comments WHERE CommentID = :commentId");
         $stmt->execute([':commentId' => $commentID]);
         $comment = $stmt->fetch(PDO::FETCH_ASSOC);

         if ($comment && $comment['UserID'] == $userID) {
             $stmt = $db->prepare("DELETE FROM Comments WHERE CommentID = :commentId");
             $stmt->execute([':commentId' => $commentID]);
         }
     }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['action'])) {
            $postID = intval($_POST['post_id']);
            $action = $_POST['action'];

            try {
                if ($action === 'unlike') {
                    $stmt = $db->prepare("DELETE FROM Likes WHERE UserID = :userId AND PostID = :postId");
                    $stmt->execute([':userId' => $userID, ':postId' => $postID]);
                    $stmt = $db->prepare("DELETE FROM Notification WHERE UserID = :userId AND PostID = :postId AND ActionType = 'Like'");
                    $stmt->execute([':userId' => $userID, ':postId' => $postID]);
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
                exit();
            }
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}
try {
    $sql = "SELECT Post.PostID, Post.Image, Post.Caption, User.Username FROM Post JOIN User ON Post.UserID = User.UserID ORDER BY Post.UploadDate DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching posts: " . $e->getMessage();
    $posts = [];
}

//shows the pinned post
$sql = "SELECT Post.PostID, Post.Image, Post.Caption, Post.IsPinned, User.Username  FROM Post JOIN User ON Post.UserID = User.UserID ORDER BY Post.IsPinned DESC, Post.UploadDate DESC";
$stmt = $db->prepare($sql);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);


//fetch trending posts
try {
    $sql = "SELECT Post.PostID, Post.Image, Post.Caption, User.Username FROM Post JOIN User ON Post.UserID = User.UserID WHERE Post.IsTrending = TRUE ORDER BY Post.UploadDate DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $trendingPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching trending posts: " . $e->getMessage();
    $trendingPosts = [];
}

//fetch user status
$stmt = $db->prepare("SELECT Status FROM User WHERE UserID = :userID");
$stmt->execute([':userID' => $_SESSION['user_id']]);
$userStatus = $stmt->fetch(PDO::FETCH_ASSOC)['Status'] ?? null;

if ($userStatus !== 'Active') {
    echo "Your account is blocked.";
    exit();
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
            <p class="bio"><?php echo htmlspecialchars($userProfile['Bio'] ?? ''); ?></p>

        </div>
    </div>

    <div class="tabs">
        <a href="#" class="active" onclick="showSection('posts')">POSTS</a>
        <a href="#" onclick="showSection('trending')">TRENDING</a>
    </div>
    <div id="trending" class="post-section">
        <h3>Trending Posts</h3>
        <div class="post-grid">
            <?php if (!empty($trendingPosts)): ?>
                <?php foreach ($trendingPosts as $post): ?>
                    <div class="post" id="post-<?php echo $post['PostID']; ?>">
                        <img src="<?php echo htmlspecialchars($post['Image']); ?>" alt="Post Image" width="300">
                        <p><?php echo htmlspecialchars($post['Caption']); ?></p>
                        <p>Posted by: <?php echo htmlspecialchars($post['Username']); ?></p>

                        <form method="POST">
                            <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                            <button name="action" value="like" type="submit">Like</button>
                            <button name="action" value="unlike" type="submit">Unlike</button>
                            <span class="like-count"><?php echo fetchLikeCount($db, $post['PostID']); ?> Likes</span>
                            <a href="../src/views/likes.php?post_id=<?php echo $post['PostID']; ?>">See All Likes</a>
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
                            <?php foreach (fetchComments($db, $post['PostID'], 3) as $comment): ?>
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
                        <a href="../src/views/comments.php?post_id=<?php echo $post['PostID']; ?>">View All Comments</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No trending posts available.</p>
            <?php endif; ?>
        </div>
    </div>

    <section id="posts" class="post-section">
        <div class="post-grid">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post" id="post-<?php echo $post['PostID']; ?>">
                        <img src="<?php echo htmlspecialchars($post['Image']); ?>" alt="Post Image" width="300">
                        <p><?php echo htmlspecialchars($post['Caption']); ?></p>
                        <p>Posted by: <?php echo htmlspecialchars($post['Username']); ?></p>

                        <form method="POST">
                            <?php if ($userStatus !== 'Active'): ?>
                                <p>Your account is blocked. You cannot like,comment, or post.</p>
                            <?php else: ?>
                            <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                            <button name="action" value="like" type="submit">Like</button>
                            <button name="action" value="unlike" type="submit">Unlike</button>
                            <span class="like-count"><?php echo fetchLikeCount($db, $post['PostID']); ?> Likes</span>
                            <a href="../src/views/likes.php?post_id=<?php echo $post['PostID']; ?>">See All Likes</a>
                            <?php endif; ?>
                        </form>

                        <form method="POST" class="comment-form">
                            <?php if ($userStatus !== 'Active'): ?>
                                <p>Your account is blocked. You cannot like,comment, or post.</p>
                            <?php else: ?>
                            <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                            <label>
                                <textarea name="comment" placeholder="Write a comment..." required></textarea>
                            </label>
                            <button name="action" value="comment" type="submit">Post Comment</button>
                            <?php endif; ?>
                        </form>

                        <h3>Comments</h3>
                        <ul>
                            <?php foreach (fetchComments($db, $post['PostID'], 3) as $comment): ?>
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
                        <a href="../src/views/comments.php?post_id=<?php echo $post['PostID']; ?>">View All Comments</a>
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
