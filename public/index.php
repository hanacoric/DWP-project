<?php
global $db;
session_start();

require_once '../src/includes/db.php';
require_once '../src/classes/user.php';
require_once '../src/classes/post.php';
require_once '../src/classes/notification.php';
require_once '../src/classes/auth.php';

//csrf token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

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

//fetch user details
$stmt = $db->prepare("SELECT User.Username, User.Email, UserProfile.Bio, UserProfile.BlobProfilePicture FROM User LEFT JOIN UserProfile ON User.UserID = UserProfile.UserID WHERE User.UserID = :userId");
$stmt->bindParam(':userId', $userID, PDO::PARAM_INT);
$stmt->execute();
$userProfile = $stmt->fetch(PDO::FETCH_ASSOC);


function fetchLikeCount($db, $postID) {
    $stmt = $db->prepare("SELECT COUNT(*) as likeCount FROM Likes WHERE PostID = :postId");
    $stmt->execute(['postId' => $postID]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['likeCount'];
}

//fetch recent comments using view
function fetchComments($db, $postID, $limit = 3) {
    $stmt = $db->prepare("SELECT * FROM RecentComments WHERE PostID = :postId ORDER BY Timestamp DESC LIMIT :commentLimit");
    $stmt->bindParam(':postId', $postID, PDO::PARAM_INT);
    $stmt->bindParam(':commentLimit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['action'])) {
    $postID = intval($_POST['post_id']);
    $action = $_POST['action'];
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validate CSRF Token
    if (!validateCsrfToken($csrfToken)) {
        die("CSRF token validation failed.");
    }

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
            $csrfToken = $_POST['csrf_token'] ?? '';

            // Validate CSRF Token
            if (!validateCsrfToken($csrfToken)) {
                die("CSRF token validation failed.");
            }


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
    try {
        if ($action === 'delete_post') {
            $stmt = $db->prepare("SELECT UserID FROM Post WHERE PostID = :postId");
            $stmt->execute([':postId' => $postID]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($post && $post['UserID'] == $userID) {
                $stmtDelete = $db->prepare("DELETE FROM Post WHERE PostID = :postId");
                $stmtDelete->execute([':postId' => $postID]);

                echo "Post deleted successfully.";
            } else {
                echo "Error: You are not authorized to delete this post.";
            }
        } elseif ($action === 'like') {
        } elseif ($action === 'unlike') {
        } elseif ($action === 'comment') {
        } elseif ($action === 'delete_comment') {
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}
try {
    $sql = "SELECT Post.PostID, Post.UserID, Post.Image, Post.BlobImage, Post.Caption, User.Username FROM Post JOIN User ON Post.UserID = User.UserID ORDER BY Post.UploadDate DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching posts: " . $e->getMessage();
    $posts = [];
}

// Fetch all posts with pinned posts first
try {
    $sql = "SELECT Post.PostID, Post.UserID, Post.Image, Post.BlobImage, Post.Caption, Post.IsPinned, User.Username 
            FROM Post 
            JOIN User ON Post.UserID = User.UserID 
            ORDER BY Post.IsPinned DESC, Post.UploadDate DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $allPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching posts: " . $e->getMessage();
    $allPosts = [];
}





//fetch trending posts using view
try {
    $sql = "SELECT DISTINCT PostID, UserID, Image, BlobImage, Caption, Username FROM TrendingPosts";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $trendingPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($trendingPosts as $key => $post) {
        if (!empty($post['BlobImage'])) {
            $trendingPosts[$key]['Image'] = 'data:image/jpeg;base64,' . base64_encode($post['BlobImage']);
        } else {
            $trendingPosts[$key]['Image'] = '/path/to/default/image.png';
        }
    }

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

// Fetch searched user
$searchResults = [];
$posts = [];

if (!empty($_GET['search_user'])) {
    $searchQuery = trim($_GET['search_user']);
    try {
        $stmt = $db->prepare("
            SELECT User.UserID, User.Username, User.Email, UserProfile.Bio, UserProfile.BlobProfilePicture 
            FROM User 
            LEFT JOIN UserProfile ON User.UserID = UserProfile.UserID 
            WHERE User.Username LIKE :search
        ");
        $stmt->execute([':search' => '%' . $searchQuery . '%']);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($searchResults)) {
            $selectedUserID = $searchResults[0]['UserID'];

            $stmt = $db->prepare("
                SELECT Post.PostID, Post.Caption, Post.BlobImage 
                FROM Post 
                WHERE Post.UserID = :userId 
                ORDER BY Post.UploadDate DESC
            ");
            $stmt->execute([':userId' => $selectedUserID]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        echo "Error searching user or fetching posts: " . $e->getMessage();
    }
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
<?php include '../../DWP/src/views/sidebar.php'; ?>

<div class="main-content">
    <div class="profile-section">
        <img src="<?php echo isset($userProfile['BlobProfilePicture'])
            ? 'data:image/jpeg;base64,' . base64_encode($userProfile['BlobProfilePicture'])
            : '/DWP/public/assets/images/profileicon.png'; ?>" alt="Profile Image" class="profile-image">
        <div class="profile-info">
            <h2><?php echo htmlspecialchars($userProfile['Username'] ?? ''); ?></h2>
            <p><?php echo htmlspecialchars($userProfile['Email'] ?? ''); ?></p>
            <p><?php echo htmlspecialchars($userProfile['Bio'] ?? 'No bio available.'); ?></p>
        </div>
    </div>

    <div class="tabs">
        <a href="#" class="active" onclick="showSection('posts')">POSTS</a>
        <a href="#" onclick="showSection('trending')">TRENDING</a>
    </div>

    <form method="GET" class="search-form" style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
        <label>
            <input type="text" name="search_user" placeholder="Search for users..." value="<?php echo isset($_GET['search_user']) ? htmlspecialchars($_GET['search_user']) : ''; ?>" required>
        </label>
        <button type="submit">Search</button>
    </form>

    <?php if (!empty($_GET['search_user'])): ?>
        <a href="admin.php" class="back-to-home">Back to Home</a>
    <?php endif; ?>

    <?php if (!empty($searchResults)): ?>
        <section class="search-results">
            <h3>User Information</h3>
            <?php foreach ($searchResults as $result): ?>
                <div class="user-result">
                    <h4>Username: <?php echo htmlspecialchars($result['Username']); ?></h4>
                    <p>Bio: <?php echo htmlspecialchars($result['Bio']); ?></p>
                    <p>Email: <?php echo htmlspecialchars($result['Email']); ?></p>
                </div>
            <?php endforeach; ?>

            <h3>Posts by <?php echo htmlspecialchars($searchResults[0]['Username']); ?></h3>
            <div class="post-grid">
                <?php foreach ($posts as $post): ?>
                    <div class="post" id="post-<?php echo $post['PostID']; ?>">
                        <?php if (!empty($post['BlobImage'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($post['BlobImage']); ?>" alt="Post Image" width="300">
                        <?php endif; ?>
                        <p><?php echo htmlspecialchars($post['Caption']); ?></p>

                        <form method="POST">
                            <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <button name="action" value="like" type="submit">Like</button>
                            <button name="action" value="unlike" type="submit">Unlike</button>
                            <span class="like-count"><?php echo fetchLikeCount($db, $post['PostID']); ?> Likes</span>
                            <a href="../src/views/likes.php?post_id=<?php echo $post['PostID']; ?>">View All Likes</a>
                        </form>

                        <form method="POST" class="comment-form">
                            <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <label>
                                <textarea name="comment" placeholder="Write a comment..." required></textarea>
                            </label>
                            <button name="action" value="comment" type="submit">Post Comment</button>
                        </form>

                        <h3>Comments</h3>
                        <div class="comment-list">
                            <?php $comments = fetchComments($db, $post['PostID'], 3); ?>
                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-item">
                                        <strong><?php echo htmlspecialchars($comment['Username']); ?>:</strong>
                                        <?php echo htmlspecialchars($comment['Comment']); ?>
                                        <span>(<?php echo $comment['Timestamp']; ?>)</span>
                                        <?php if ($comment['UserID'] == $userID): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['CommentID']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <button name="action" value="delete_comment" type="submit">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <a href="../src/views/comments.php?post_id=<?php echo $post['PostID']; ?>">View All Comments</a>
                            <?php else: ?>
                                <p>No comments yet. Be the first to comment!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <div id="trending" class="post-section">
        <h3>Trending Posts</h3>
        <div class="post-grid">
            <?php if (!empty($trendingPosts)): ?>
                <?php foreach ($trendingPosts as $post): ?>
                    <div class="post" id="post-<?php echo $post['PostID']; ?>">
                        <!-- Post Image -->
                        <?php if (!empty($post['BlobImage'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($post['BlobImage']); ?>" alt="Post Image" width="300">
                        <?php else: ?>
                            <img src="/DWP/public/assets/images/default-image.png" alt="Default Image" width="300">
                        <?php endif; ?>

                        <!-- Post Caption and User -->
                        <p><?php echo htmlspecialchars($post['Caption']); ?></p>
                        <p>Posted by: <?php echo isset($post['Username']) ? htmlspecialchars($post['Username']) : 'Unknown User'; ?></p>

                        <!-- Like and Unlike Buttons -->
                        <form method="POST">
                            <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <button name="action" value="like" type="submit">Like</button>
                            <button name="action" value="unlike" type="submit">Unlike</button>
                            <span class="like-count"><?php echo fetchLikeCount($db, $post['PostID']); ?> Likes</span>
                            <a href="../src/views/likes.php?post_id=<?php echo $post['PostID']; ?>">View All Likes</a>
                        </form>

                        <!-- Comment Box -->
                        <form method="POST" class="comment-form">
                            <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <label>
                                <textarea name="comment" placeholder="Write a comment..." required></textarea>
                            </label>
                            <button name="action" value="comment" type="submit">Post Comment</button>
                        </form>

                        <!-- Comments Section -->
                        <h3>Comments</h3>
                        <div class="comment-list">
                            <?php $comments = fetchComments($db, $post['PostID'], 3); ?>
                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-item">
                                        <strong><?php echo htmlspecialchars($comment['Username']); ?>:</strong>
                                        <?php echo htmlspecialchars($comment['Comment']); ?>
                                        <span>(<?php echo $comment['Timestamp']; ?>)</span>
                                        <?php if ($comment['UserID'] == $userID): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['CommentID']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <button name="action" value="delete_comment" type="submit">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <a href="../src/views/comments.php?post_id=<?php echo $post['PostID']; ?>">View All Comments</a>
                            <?php else: ?>
                                <p>No comments yet. Be the first to comment!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No trending posts available.</p>
            <?php endif; ?>
        </div>
    </div>


    <section id="posts" class="post-section">
        <h3>All Posts</h3>
        <div class="post-grid">
            <?php if (!empty($allPosts)): ?>
                <?php foreach ($allPosts as $post): ?>
                    <div class="post" id="post-<?php echo $post['PostID']; ?>">
                        <?php if (!empty($post['BlobImage'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($post['BlobImage']); ?>" alt="Post Image" width="300">
                        <?php else: ?>
                            <img src="/DWP/public/assets/images/default-image.png" alt="Default Image" width="300">
                        <?php endif; ?>

                        <p><?php echo htmlspecialchars($post['Caption']); ?></p>
                        <p>Posted by: <?php echo isset($post['Username']) ? htmlspecialchars($post['Username']) : 'Unknown User'; ?></p>

                        <form method="POST">
                            <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <button name="action" value="like" type="submit">Like</button>
                            <button name="action" value="unlike" type="submit">Unlike</button>
                            <span class="like-count"><?php echo fetchLikeCount($db, $post['PostID']); ?> Likes</span>
                            <a href="../src/views/likes.php?post_id=<?php echo $post['PostID']; ?>">View All Likes</a>
                        </form>

                        <form method="POST" class="comment-form">
                            <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <label>
                                <textarea name="comment" placeholder="Write a comment..." required></textarea>
                            </label>
                            <button name="action" value="comment" type="submit">Post Comment</button>
                        </form>

                        <h3>Comments</h3>
                        <div class="comment-list">
                            <?php $comments = fetchComments($db, $post['PostID'], 3); ?>
                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-item">
                                        <strong><?php echo htmlspecialchars($comment['Username']); ?>:</strong>
                                        <?php echo htmlspecialchars($comment['Comment']); ?>
                                        <span>(<?php echo $comment['Timestamp']; ?>)</span>
                                        <?php if ($comment['UserID'] == $userID): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['CommentID']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <button name="action" value="delete_comment" type="submit">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <a href="../src/views/comments.php?post_id=<?php echo $post['PostID']; ?>">View All Comments</a>
                            <?php else: ?>
                                <p>No comments yet. Be the first to comment!</p>
                            <?php endif; ?>
                        </div>

                        <?php if ($post['UserID'] == $userID): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <button name="action" value="delete_post" type="submit" onclick="return confirm('Are you sure you want to delete this post?')">Delete Post</button>
                            </form>
                        <?php endif; ?>
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

<style>
    .search-form {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        width: 100%;
        margin-bottom: 20px;
        position: absolute;
        right: 20px; /* Ensures it's positioned on the right side */
        top: 10px; /* Adjust vertically as needed */
    }

    .search-form input[type="text"] {
        padding: 10px 15px;
        border: 2px solid #FFDF00;
        border-right: none;
        border-radius: 25px 0 0 25px;
        outline: none;
        font-size: 1em;
        background-color: #333;
        color: white;
        transition: all 0.3s ease;
        width: 200px; /* Adjust as needed */
    }

    .search-form input[type="text"]::placeholder {
        color: #aaa;
        font-style: italic;
    }

    .search-form input[type="text"]:focus {
        border-color: #FFBF00;
    }

    .search-form button {
        background-color: #FFDF00;
        color: black;
        border: 2px solid #FFDF00;
        border-left: none;
        padding: 10px 20px;
        font-weight: bold;
        font-size: 1em;
        border-radius: 0 25px 25px 0;
        cursor: pointer;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .search-form button:hover {
        background-color: #FFBF00;
        color: white;
</style>


