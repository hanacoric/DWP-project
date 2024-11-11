<?php
session_start();

require_once '../src/includes/db.php';
require_once '../src/classes/user.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$userObj = new User($db);

$userID = $_SESSION['user_id'];
$userProfile = $userObj->getUserProfile($userID);

try {
    $sql = "SELECT PostID, Image, Caption FROM Post WHERE UserID = :userID ORDER BY UploadDate DESC";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching posts: " . $e->getMessage();
    $posts = [];
}

//trending posts
try {
    $trendingSql = "SELECT PostID, Image, Caption FROM Post WHERE UserID = :userID AND Trending = TRUE ORDER BY UploadDate DESC";
    $trendingStmt = $db->prepare($trendingSql);
    $trendingStmt->bindParam(':userID', $userID, PDO::PARAM_INT);
    $trendingStmt->execute();
    $trendingPosts = $trendingStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching trending posts: " . $e->getMessage();
    $trendingPosts = [];
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
                    <div class="post">
                        <img src="<?php echo htmlspecialchars($post['Image']); ?>" alt="Post Image">
                        <p><?php echo htmlspecialchars($post['Caption']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No posts available.</p>
            <?php endif; ?>
        </div>
    </section>

    <section id="trending" class="post-section" style="display: none;">
        <div class="post-grid">
            <?php if (!empty($trendingPosts)): ?>
                <?php foreach ($trendingPosts as $post): ?>
                    <div class="post">
                        <img src="<?php echo htmlspecialchars($post['Image']); ?>" alt="Trending Post Image">
                        <p><?php echo htmlspecialchars($post['Caption']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No trending posts available.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<script src="assets/js/home.js"></script>

</body>
</html>
