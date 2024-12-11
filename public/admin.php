<?php
global $db;
session_start();

require_once '../src/includes/db.php';
require_once '../src/classes/user.php';
require_once '../src/classes/post.php';
require_once '../src/classes/notification.php';
require_once '../src/classes/auth.php';
$auth = new Auth($db);

if (!$auth->isAdmin()) {
    header("Location: index.php");
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


//ADMIN STUFF

$userID = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT Role.RoleName FROM User JOIN Role ON User.RoleID = Role.RoleID WHERE User.UserID = :userId");
$stmt->execute([':userId' => $userID]);
$role = $stmt->fetch(PDO::FETCH_ASSOC)['RoleName'];

if ($role !== 'Admin') {
    header("Location: index.php");
    exit();
}

//delete post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['post_id'])) {
    $action = $_POST['action'];
    $postID = intval($_POST['post_id']);

    if ($action === 'delete_post') {
        try {
            $stmt = $db->prepare("DELETE FROM Post WHERE PostID = :postId");
            $stmt->execute([':postId' => $postID]);
            $successMessage = "Post deleted successfully.";
        } catch (PDOException $e) {
            echo "Error deleting post: " . $e->getMessage();
        }
    }
}

//pin/unpin posts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['post_id'])) {
    $postID = intval($_POST['post_id']);
    $action = $_POST['action'];

    try {
        if ($action === 'pin') {
            $stmt = $db->prepare("UPDATE Post SET IsPinned = TRUE WHERE PostID = :postId");
            $stmt->execute([':postId' => $postID]);
        } elseif ($action === 'unpin') {
            $stmt = $db->prepare("UPDATE Post SET IsPinned = FALSE WHERE PostID = :postId");
            $stmt->execute([':postId' => $postID]);
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// default fetch all posts
$posts = [];
$searchResults = [];
if (empty($_GET['search_user'])) {
    try {
        $sql = "SELECT Post.PostID, Post.Image, Post.Caption, Post.IsPinned, User.Username, User.Status FROM Post  JOIN User ON Post.UserID = User.UserID ORDER BY Post.UploadDate DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error fetching posts: " . $e->getMessage();
    }
} else {
    $searchQuery = trim($_GET['search_user']);
    try {
        //fetch user info
        $stmt = $db->prepare("SELECT User.UserID, User.Username, User.Status, UserProfile.Bio FROM User  LEFT JOIN UserProfile ON User.UserID = UserProfile.UserID WHERE User.Username LIKE :search");
        $stmt->execute([':search' => '%' . $searchQuery . '%']);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($searchResults)) {
            $selectedUserID = $searchResults[0]['UserID'];

            $stmt = $db->prepare("SELECT Post.PostID, Post.Image, Post.Caption, Post.IsPinned FROM Post WHERE Post.UserID = :userId ORDER BY Post.UploadDate DESC");
            $stmt->execute([':userId' => $selectedUserID]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        echo "Error searching user or fetching posts: " . $e->getMessage();
    }
}

//trending/not trending posts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['action'])) {
    $postID = intval($_POST['post_id']);
    $action = $_POST['action'];

    try {
        if ($action === 'trending') {
            $stmt = $db->prepare("UPDATE Post SET IsTrending = TRUE WHERE PostID = :postId");
            $stmt->execute([':postId' => $postID]);
        } elseif ($action === 'not_trending') {
            $stmt = $db->prepare("UPDATE Post SET IsTrending = FALSE WHERE PostID = :postId");
            $stmt->execute([':postId' => $postID]);
        }
    } catch (PDOException $e) {
        echo "Error updating trending status: " . $e->getMessage();
    }
}


//fetch trending posts
try {
    $sql = "SELECT Post.PostID, Post.Image, Post.Caption, Post.IsPinned, User.Username, User.Status FROM Post  JOIN User ON Post.UserID = User.UserID WHERE Post.IsTrending = TRUE ORDER BY Post.UploadDate DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $trendingPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching trending posts: " . $e->getMessage();
    $trendingPosts = [];
}

 //block/unblock/delete users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $userID = intval($_POST['user_id']);
    $action = $_POST['action'];

    if ($action === 'block') {
        if ($userObj->blockUser($userID)) {
            echo "User blocked successfully.";
        }
    } elseif ($action === 'unblock') {
        if ($userObj->unblockUser($userID)) {
            echo "User unblocked successfully.";
        }
    } elseif ($action === 'delete') {
        if ($userObj->deleteUserPermanently($userID)) {
            echo "User deleted successfully.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - RandomShot</title>
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
</head>
<body>
<div class="admin-header">
    <form method="POST" action="logout.php" class="logout-form">
        <button type="submit" class="logout-button">Logout</button>
    </form>
</div>

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

    <div id="trending" class="post-section" style="display: none;">
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
                            <button name="action" value="not_trending" type="submit">Mark as Not Trending</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No trending posts available.</p>
            <?php endif; ?>
        </div>
    </div>


    <form method="GET" class="search-form" style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
        <label>
            <input type="text" name="search_user" placeholder="Search for users..." value="<?php echo isset($_GET['search_user']) ? htmlspecialchars($_GET['search_user']) : ''; ?>" required>
        </label>
        <button type="submit">Search</button>
    </form>

    <?php if (empty($_GET['search_user'])): ?>
        <section id="posts" class="post-section">
            <h3>All Posts</h3>
            <div class="post-grid">
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post" id="post-<?php echo $post['PostID']; ?>">
                            <img src="<?php echo htmlspecialchars($post['Image']); ?>" alt="Post Image" width="300">
                            <p><?php echo htmlspecialchars($post['Caption']); ?></p>
                            <p>Posted by: <?php echo htmlspecialchars($post['Username']); ?> (<?php echo htmlspecialchars($post['Status']); ?>)</p>
                            <form method="POST">
                                <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                                <button name="action" value="delete_post" type="submit">Delete</button>
                                <?php if (isset($post['IsPinned']) && $post['IsPinned']): ?>
                                    <button name="action" value="unpin" type="submit">Unpin</button>
                                <?php else: ?>
                                    <button name="action" value="pin" type="submit">Pin</button>
                                <?php endif; ?>
                                <?php if (isset($post['IsTrending']) && $post['IsTrending']): ?>
                                    <button name="action" value="not_trending" type="submit">Mark as Not Trending</button>
                                <?php else: ?>
                                    <button name="action" value="trending" type="submit">Mark as Trending</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No posts available.</p>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>


        <!--user info search results-->
        <?php if (!empty($_GET['search_user'])): ?>
            <a href="admin.php" class="back-to-home">Back to Home</a>
        <?php endif; ?>

        <section class="search-results">
            <?php if (!empty($searchResults)): ?>
                <h3>User Information</h3>
                <?php foreach ($searchResults as $result): ?>
                    <div class="user-result">
                        <h4>Username: <?php echo htmlspecialchars($result['Username']); ?></h4>
                        <p>Status: <?php echo htmlspecialchars($result['Status']); ?></p>
                        <p>Bio: <?php echo htmlspecialchars($result['Bio']); ?></p>
                        <input type="hidden" name="user_id" value="<?php echo $result['UserID']; ?>">
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $result['UserID']; ?>">
                            <?php if ($result['Status'] === 'Active'): ?>
                                <button name="action" value="block" type="submit">Block</button>
                            <?php else: ?>
                                <button name="action" value="unblock" type="submit">Unblock</button>
                            <?php endif; ?>
                            <button name="action" value="delete" type="submit" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                        </form>

                    </div>
                <?php endforeach; ?>

                <h3>Posts by <?php echo htmlspecialchars($searchResults[0]['Username']); ?></h3>
                <div class="post-grid">
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="post" id="post-<?php echo $post['PostID']; ?>">
                                <img src="<?php echo htmlspecialchars($post['Image']); ?>" alt="Post Image" width="300">
                                <p><?php echo htmlspecialchars($post['Caption']); ?></p>
                                <form method="POST">
                                    <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                                    <button name="action" value="delete_post" type="submit">Delete</button>
                                    <?php if (isset($post['IsPinned']) && $post['IsPinned']): ?>
                                        <button name="action" value="unpin" type="submit">Unpin</button>
                                    <?php else: ?>
                                        <button name="action" value="pin" type="submit">Pin</button>
                                    <?php endif; ?>
                                    <?php if (isset($post['IsTrending']) && $post['IsTrending']): ?>
                                        <button name="action" value="not_trending" type="submit">Mark as Not Trending</button>
                                    <?php else: ?>
                                        <button name="action" value="trending" type="submit">Mark as Trending</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No posts available for this user.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p>No users found for "<?php echo htmlspecialchars($searchQuery); ?>".</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<script src="assets/js/home.js"></script>
</body>
</html>


