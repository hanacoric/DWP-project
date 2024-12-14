<?php
session_start();
global $db;
require_once '../includes/db.php';
require_once '../classes/user.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: /DWP/public/login.php");
    exit();
}

$userObj = new User($db);

$userID = $_SESSION['user_id'];

// CSRF Token Functions
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

$userProfile = $userObj->getUserProfile($userID);

if (!$userProfile) {
    echo "Error: Unable to load profile information.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_GET['update'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validate CSRF Token
    if (!validateCsrfToken($csrfToken)) {
        die("CSRF token validation failed.");
    }

    $newUsername = trim($_POST['username']);
    $newBio = trim($_POST['bio']);
    $newEmail = trim($_POST['email']);
    $newGender = trim($_POST['gender']);
    $newName = trim($_POST['name']);

    echo "Form submitted. Values being passed to updateUserProfile:";
    echo "UserID: $userID, Bio: $newBio, Gender: $newGender, Name: $newName<br>";


    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $imagePath = '/DWP/public/assets/images/profilePicture/' . basename($_FILES['profile_picture']['name']);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $imagePath);
        $userObj->updateProfilePicture($userID, $imagePath);
    }

    if ($userObj->updateUserProfile($userID, $newBio, $newGender, $newName)) {
        echo "Profile updated successfully!";
    } else {
        echo "Failed to update profile.";
    }

    $userProfile = $userObj->getUserProfile($userID);

    header("Location: profile.php?update=success");
    exit();
}

try {
    $db->query("SELECT 1");
    echo "Database connection is operational.";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - RandomShot</title>
    <link rel="stylesheet" href="../../../DWP/public/assets/css/profile.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="profile-container">
    <form method="POST" enctype="multipart/form-data" action="profile.php">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div class="profile-header">
            <div class="profile-picture-section">
                <img src="<?php echo isset($userProfile['ProfilePicture']) ? htmlspecialchars($userProfile['ProfilePicture']) : '/DWP/public/assets/images/profileicon.png'; ?>" alt="Profile Picture" class="profile-picture">
                <label for="profile_picture" class="change-photo-btn">Change photo</label>
                <input type="file" name="profile_picture" id="profile_picture" style="display: none;">
            </div>
            <div class="profile-info">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($userProfile['Username']); ?>" maxlength="50">
            </div>
        </div>

        <div class="profile-fields">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" maxlength="100"><?php echo htmlspecialchars(isset($userProfile['Bio']) ? $userProfile['Bio'] : ''); ?></textarea>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userProfile['Email']); ?>" maxlength="100">

            <label for="gender">Gender</label>
            <select id="gender" name="gender">
                <option value="Male" <?php echo ($userProfile['Gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo ($userProfile['Gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo ($userProfile['Gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>

            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars(isset($userProfile['FirstLast']) ? $userProfile['FirstLast'] : ''); ?>" maxlength="100">
        </div>

        <div class="edit-section">
            <button type="submit">Save Changes</button>
        </div>
    </form>

    <?php if (isset($_GET['update']) && $_GET['update'] === 'success'): ?>
        <p>Profile updated successfully!</p>
    <?php endif; ?>
</div>
</body>
</html>
