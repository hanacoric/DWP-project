<?php
global $db;
session_start();
require_once '../src/includes/db.php';
require_once '../src/classes/user.php';
require_once '../src/classes/post.php';
require_once '../src/classes/notification.php';
require_once '../src/classes/auth.php';

// Generate CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

// ADMIN STUFF
$stmt = $db->prepare("SELECT Role.RoleName FROM User JOIN Role ON User.RoleID = Role.RoleID WHERE User.UserID = :userId");
$stmt->execute([':userId' => $userID]);
$role = $stmt->fetch(PDO::FETCH_ASSOC)['RoleName'];

if ($role !== 'Admin') {
    header("Location: index.php");
    exit();
}

// POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    if (isset($_POST['action'], $_POST['post_id'])) {
        $postID = intval($_POST['post_id']);
        $action = $_POST['action'];

        try {
            $query = "";
            $params = [':postId' => $postID];

            switch ($action) {
                case 'delete_post':
                    $query = "DELETE FROM Post WHERE PostID = :postId";
                    break;
                case 'pin':
                    $query = "UPDATE Post SET IsPinned = TRUE WHERE PostID = :postId";
                    break;
                case 'unpin':
                    $query = "UPDATE Post SET IsPinned = FALSE WHERE PostID = :postId";
                    break;
            }

            if (!empty($query)) {
                $stmt = $db->prepare($query);
                $stmt->execute($params);
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    if (isset($_POST['user_id'], $_POST['action'])) {
        $userID = intval($_POST['user_id']);
        $action = $_POST['action'];

        try {
            switch ($action) {
                case 'block':
                    $userObj->blockUser($userID);
                    echo "User blocked successfully.";
                    break;
                case 'unblock':
                    $userObj->unblockUser($userID);
                    echo "User unblocked successfully.";
                    break;
                case 'delete':
                    $userObj->deleteUserPermanently($userID);
                    echo "User deleted successfully.";
                    break;
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

// Fetch all posts as default
$posts = [];
$searchResults = [];
if (empty($_GET['search_user'])) {
    try {
        $sql = "SELECT Post.PostID, Post.Image, Post.BlobImage, Post.Caption, Post.IsPinned, Post.IsTrending, User.Username, User.Status FROM Post  JOIN User ON Post.UserID = User.UserID ORDER BY Post.UploadDate DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error fetching posts: " . $e->getMessage();
    }
} else {
    $searchQuery = trim($_GET['search_user']);
    try {
        $stmt = $db->prepare("SELECT User.UserID, User.Username, User.Status, UserProfile.Bio FROM User  LEFT JOIN UserProfile ON User.UserID = UserProfile.UserID WHERE User.Username LIKE :search");
        $stmt->execute([':search' => '%' . $searchQuery . '%']);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($searchResults)) {
            $selectedUserID = $searchResults[0]['UserID'];

            $stmt = $db->prepare("SELECT Post.PostID, Post.Image, Post.BlobImage, Post.Caption, Post.IsPinned FROM Post WHERE Post.UserID = :userId ORDER BY Post.UploadDate DESC");
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
    <title>Admin Panel - RandomShot</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
</head>
<body>

<div class="main-content">
    <div class="profile-section">
        <img src="<?php echo isset($userProfile['ProfilePicture']) ? htmlspecialchars($userProfile['ProfilePicture']) : 'assets/images/profileicon.png'; ?>" alt="Profile Image" class="profile-image">
        <div class="profile-info">
            <h2 class="username"><?php echo htmlspecialchars($userProfile['Username']); ?></h2>
            <p class="bio"><?php echo htmlspecialchars($userProfile['Bio'] ?? ''); ?></p>
        </div>
        <form method="POST" action="logout.php" class="logout-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </div>

    <form method="GET" class="search-form" style="display: flex; justify-content: flex-end;">
        <label>
            <input type="text" name="search_user" placeholder="Search for users..." value="<?php echo isset($_GET['search_user']) ? htmlspecialchars($_GET['search_user']) : ''; ?>" required>
        </label>
        <button type="submit">Search</button>
    </form>


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
                        <?php if (!empty($post['BlobImage'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($post['BlobImage']); ?>" alt="Post Image" width="300">
                        <?php endif; ?>
                        <p><?php echo htmlspecialchars($post['Caption']); ?></p>
                        <p>Posted by: <?php echo htmlspecialchars($post['Username']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No trending posts available.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($_GET['search_user'])): ?>
        <section id="posts" class="post-section">
            <h3>All Posts</h3>
            <div class="post-grid">
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post" id="post-<?php echo $post['PostID']; ?>">
                            <?php if (!empty($post['BlobImage'])): ?>
                                <!-- converts blob to base64 and displays it -->
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($post['BlobImage']); ?>" alt="Post Image" width="300">
                            <?php endif; ?>
                            <p><?php echo htmlspecialchars($post['Caption']); ?></p>
                            <p>Posted by: <?php echo htmlspecialchars($post['Username']); ?> (<?php echo htmlspecialchars($post['Status']); ?>)</p>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                                <button name="action" value="delete_post" type="submit">Delete</button>
                                <button name="action" value="<?php echo $post['IsPinned'] ? 'unpin' : 'pin'; ?>" type="submit"><?php echo $post['IsPinned'] ? 'Unpin' : 'Pin'; ?></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No posts available.</p>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
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
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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
                                <?php if (!empty($post['BlobImage'])): ?>
                                    <!-- converts blob to base64 and displays it -->
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($post['BlobImage']); ?>" alt="Post Image" width="300">
                                <?php endif; ?>
                                <p><?php echo htmlspecialchars($post['Caption']); ?></p>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                                    <button name="action" value="delete_post" type="submit">Delete</button>
                                    <?php if (isset($post['IsPinned']) && $post['IsPinned']): ?>
                                        <button name="action" value="unpin" type="submit">Unpin</button>
                                    <?php else: ?>
                                        <button name="action" value="pin" type="submit">Pin</button>
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

<style>
    .search-form {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        width: 50%;
        position: absolute;
        right: 20px;
        top: 10px;
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
        width: 200px;
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
