<?php
session_start();
global $db;
require_once '../includes/db.php';
require_once '../classes/user.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /DWP/public/login.php");
    exit();
}

$userID = intval($_SESSION['user_id']);
if ($userID <= 0) {
    die("Error: Invalid UserID in session.");
}

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

$userObj = new User($db);

//fetch user profile
$stmt = $db->prepare("SELECT User.Username, User.Email, UserProfile.Bio, UserProfile.Gender, UserProfile.FirstLast, UserProfile.BlobProfilePicture FROM User LEFT JOIN UserProfile ON User.UserID = UserProfile.UserID WHERE User.UserID = :userId");
$stmt->bindParam(':userId', $userID, PDO::PARAM_INT);
$stmt->execute();
$userProfile = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $gender = $_POST['gender'] ?? 'Other';
    $firstLast = $_POST['name'] ?? '';

    if (!validateCsrfToken($csrfToken)) {
        die("CSRF token validation failed.");
    }

    //checks if a profile picture is uploaded
    $profilePicture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $maxFileSize = 5242880; // 5MB
        $minFileSize = 10240;   // 10KB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if ($file['size'] < $minFileSize) {
            echo "Error: The uploaded profile image is too small. Minimum size is 10KB.";
            exit();
        }
        if ($file['size'] > $maxFileSize) {
            echo "Error: The uploaded profile image is too large. Maximum size is 5MB.";
            exit();
        }

        if (!in_array($file['type'], $allowedTypes)) {
            echo "Error: Only JPG, PNG, and GIF formats are allowed.";
            exit();
        }

        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            echo "Error: The uploaded profile image is not a valid image.";
            exit();
        }
        list($width, $height) = $imageInfo;
        if ($width > 1080 || $height > 1080) {
            echo "Error: Image dimensions are too big. Maximum size is 1080x1080 pixels.";
            exit();
        }
        if ($width < 100 || $height < 100) {
            echo "Error: Image dimensions are too small. Minimum size is 100x100 pixels.";
            exit();
        }

        $profilePicture = file_get_contents($file['tmp_name']);
    }

    //update user profile
    $result = $userObj->updateUserProfile($userID, $bio, $gender, $firstLast, $profilePicture);

    if ($result) {
        header("Location: profile.php?update=success");
        exit();
    } else {
        echo "Failed to update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - RandomShot</title>
    <link rel="stylesheet" href="../../public/assets/css/profile.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="profile-container">
    <form method="POST" enctype="multipart/form-data" action="profile.php">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div class="profile-header">
            <div class="profile-picture-section">
                <img src="<?php echo isset($userProfile['BlobProfilePicture'])
                    ? 'data:image/jpeg;base64,' . base64_encode($userProfile['BlobProfilePicture'])
                    : '/DWP/public/assets/images/profileicon.png'; ?>"
                     alt="Profile Picture" class="profile-picture">
                <label for="profile_picture" class="change-photo-btn">Change photo</label>
                <input type="file" name="profile_picture" id="profile_picture" style="display: none;">
            </div>
            <div class="profile-info">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($userProfile['Username'] ?? ''); ?>" maxlength="50" disabled>
            </div>
        </div>
        <div class="profile-fields">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" maxlength="100"><?php echo htmlspecialchars($userProfile['Bio'] ?? ''); ?></textarea>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userProfile['Email'] ?? ''); ?>" maxlength="100" disabled>
            <label for="gender">Gender</label>
            <select id="gender" name="gender">
                <option value="Male" <?php echo ($userProfile['Gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo ($userProfile['Gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo ($userProfile['Gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userProfile['FirstLast'] ?? ''); ?>" maxlength="100">
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
