<?php
global $db;
require_once  __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../classes/auth.php';
$auth = new Auth($db);

if (!isset($_SESSION['role']) || $_SESSION['role'] === 'Admin') {
    return;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT Role.RoleName FROM User JOIN Role ON User.RoleID = Role.RoleID WHERE User.UserID = :userId");
$stmt->execute([':userId' => $userID]);
$userRole = $stmt->fetch(PDO::FETCH_ASSOC)['RoleName'] ?? 'User';

$homeURL = ($userRole === 'Admin') ? 'admin.php' : 'index.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sidebar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../public/assets/css/sidebar.css">
</head>
<body>
<div class="sidebar">
    <h1>Random<span>Shot</span></h1>
    <nav>

        <a href="../../../DWP/src/views/profile.php" class="profile-icon"><i class="fas fa-user-circle"></i> Profile</a>
        <a href="../../../DWP/public/<?php echo $homeURL; ?>" class="home-icon"><i class="fas fa-home "></i> Home</a>
        <a href="../../../DWP/src/views/upload.php" class="upload-icon"><i class="fas fa-plus-circle "></i> Upload</a>
        <a href="../../../DWP/src/views/likes_notifications.php" class="likes-icon"><i class="fas fa-heart "></i> Likes</a>
        <a href="../../../DWP/src/views/comments_notifications.php" class="comments-icon"><i class="fas fa-comment "></i> Comments</a>
        <a href="../../../DWP/src/views/settings.php" class="settings-icon"><i class="fas fa-cog "></i> Settings</a>
    </nav>
    <form method="POST" action="../../../DWP/public/logout.php" class="logout-form">
        <button type="submit" class="logout-button">Logout</button>
    </form>
</div>
</body>
</html>
