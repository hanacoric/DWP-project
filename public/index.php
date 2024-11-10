<?php
require_once '../src/includes/db.php';
require_once '../src/classes/post.php';

$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$post = new Post($db);

$recentPosts = $post->getRecentPosts();
$trendingPosts = $post->getTrendingPosts();
$username = "RandomPictures"; // Replace with actual user data
$description = "Sharing a daily dose of real-life moments. Follow and join the community!"; // Replace with actual user data
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - RandomShot</title>
    <!-- Link to home-specific CSS -->
    <link rel="stylesheet" href="assets/css/home.css">
    <!-- Link to sidebar-specific CSS -->
    <link rel="stylesheet" href="assets/css/sidebar.css">
</head>
<body>
<?php include '../src/views/sidebar.php'; ?>
<div class="main-content">
    <div class="profile-section">
        <img src="assets/images/profileicon.png" alt="Profile Image" class="profile-image">
        <div class="profile-info">
            <h2 class="username"><?php echo $username; ?></h2>
            <p class="bio"><?php echo $description; ?></p>
        </div>
    </div>

    <div class="tabs">
        <a href="#" class="active">POSTS</a>
        <a href="#">TRENDING</a>
    </div>

    <section class="posts">
        <div class="post-grid">
            <?php foreach ($recentPosts as $post): ?>
                <div class="post">
                    <img src="<?php echo $post['Image']; ?>" alt="Post Image">
                    <p><?php echo htmlspecialchars($post['Caption']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="trending">
        <div class="post-grid">
            <?php foreach ($trendingPosts as $post): ?>
                <div class="post">
                    <img src="<?php echo $post['Image']; ?>" alt="Post Image">
                    <p><?php echo htmlspecialchars($post['Caption']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
</body>
</html>
