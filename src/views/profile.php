<?php
// profile.php

require_once '../includes/db.php';
require_once '../classes/user.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /DWP/public/login.php");
    exit();
}

$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$userObj = new User($db);
$userID = $_SESSION['user_id'];
$userProfile = $userObj->getUserProfile($userID);

if (!$userProfile) {
    echo "Error: Unable to load profile information.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newUsername = htmlspecialchars($_POST['username']);
    $newBio = htmlspecialchars($_POST['bio']);
    $newEmail = htmlspecialchars($_POST['email']);
    $newGender = $_POST['gender'];
    $nameParts = explode(' ', $_POST['name'], 2);
    $newFirstName = htmlspecialchars($nameParts[0]);
    $newLastName = isset($nameParts[1]) ? htmlspecialchars($nameParts[1]) : '';

    // Check if a new profile picture was uploaded
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $imagePath = '/DWP/public/assets/images/profilePicture/' . basename($_FILES['profile_picture']['name']);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $imagePath);
        $userObj->updateProfilePicture($userID, $imagePath);
    }

    // Update the rest of the profile fields
    $userObj->updateUserProfile($userID, $newUsername, $newBio, $newEmail, $newGender, $newFirstName, $newLastName);
    header("Location: profile.php?update=success");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - RandomShot</title>
    <link rel="stylesheet" href="../../../DWP/public/assets/css/profile.css">
    <link rel="stylesheet" href="../../../DWP/public/assets/css/sidebar.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="profile-container">
    <form method="POST" enctype="multipart/form-data" action="profile.php">
        <div class="profile-header">
            <div class="profile-picture-section">
                <img src="<?php echo isset($userProfile['ProfilePicture']) ? $userProfile['ProfilePicture'] : '/DWP/public/assets/images/profileicon.png'; ?>" alt="Profile Picture" class="profile-picture">
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
                <option value="Male" <?php echo ($userProfile['Gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo ($userProfile['Gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo ($userProfile['Gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>

            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userProfile['FirstName'] . ' ' . $userProfile['LastName']); ?>" maxlength="100">
        </div>

        <div class="edit-section">
            <button type="submit">Save Changes</button>
        </div>
    </form>
</div>
<script src="../../../DWP/public/assets/js/profile.js"></script>
</body>
</html>
