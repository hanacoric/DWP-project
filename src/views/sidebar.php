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
    </nav>
    <form method="POST" action="../../../DWP/public/logout.php" class="logout-form">
        <button type="submit" class="logout-button">Logout</button>
    </form>
</div>
</body>
</html>

<style>

    .sidebar {
        background-color: #111;
        width: 300px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        padding: 20px;
        box-sizing: border-box;
        z-index: 100;
        display: flex;
        flex-direction: column;
    }

    .sidebar h1 {
        color: white;
        font-size: 2.5em;
        margin-bottom: 20px;
        text-align: center;
    }

    .sidebar h1 span {
        color: #FFDF00;
    }

    .sidebar nav {
        margin-top: 20px;
        flex-grow: 1;
    }

    .sidebar nav a {
        display: block;
        padding: 15px;
        color: white;
        text-decoration: none;
        font-weight: bold;
        font-size: 1.4em;
        border-radius: 5px;
        transition: all 0.3s ease;
    }

    .sidebar nav a:hover {
        background-color: #333;
        color: #FFDF00;
    }

    .sidebar nav a i {
        margin-right: 10px;
        font-size: 2em;
        color: #FFDF00;
    }

    .logout-button {
        background-color: #FFDF00;
        color: black;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: bold;
        cursor: pointer;
        font-size: 1em;
        transition: all 0.3s ease;
        text-align: center;
        display: block;
    }

    .logout-button:hover {
        background-color: #FFBF00;
        color: white;
    }


</style>


