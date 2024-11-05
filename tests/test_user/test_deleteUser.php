<?php
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/User.php';

// Initialize the database connection
$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize the User object
$user = new User($db);

// Test Data
$username = "testUserDelete";
$email = "testuserdelete@example.com";
$password = "securePassword123";

// Step 1: Create a test user
echo "Testing createUser for Delete Test: ";
$createResult = $user->createUser($username, $email, $password);

if ($createResult) {
    echo "User created successfully.<br>";

    // Step 2: Retrieve the UserID of the newly created user
    $stmt = $db->prepare("SELECT UserID FROM User WHERE Username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $createdUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($createdUser) {
        $userID = $createdUser['UserID'];

        // Step 3: Soft delete the user
        echo "Testing deleteUser: ";
        $deleteResult = $user->deleteUser($userID);

        if ($deleteResult) {
            echo "User marked as 'Blocked' successfully.<br>";

            // Step 4: Verify the user’s status is "Blocked"
            $deletedUser = $user->getUser($userID);

            if ($deletedUser && $deletedUser['Status'] === 'Blocked') {
                echo "Delete Verification: User status is 'Blocked' as expected.<br>";
            } else {
                echo "Delete Verification failed: User status is not 'Blocked'.<br>";
            }
        } else {
            echo "Failed to mark user as 'Blocked'.<br>";
        }

        // Step 5: Clean up by deleting the test user completely
        $stmt = $db->prepare("DELETE FROM User WHERE UserID = :userID");
        $stmt->bindParam(':userID', $userID);
        $stmt->execute();
        echo "Test user deleted from database.<br>";
    } else {
        echo "Failed to find the created user in the database.<br>";
    }
} else {
    echo "Failed to create user for delete test.<br>";
}
