<?php
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/User.php';

// Initialize the database connection
$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize the User object
$user = new User($db);

// Test Data
$username = "testUserUpdate";
$email = "testuserupdate@example.com";
$password = "securePassword123";

// Step 1: Create a test user
echo "Testing createUser for Update Test: ";
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

        // Step 3: Update the user details
        $newUsername = "updatedUser";
        $newEmail = "updateduser@example.com";

        echo "Testing updateUser: ";
        $updateResult = $user->updateUser($userID, $newUsername, $newEmail);

        if ($updateResult) {
            echo "User updated successfully.<br>";

            // Step 4: Verify the update
            $updatedUser = $user->getUser($userID);

            if ($updatedUser) {
                echo "Verification: User details after update.<br>";
                echo "Username: " . htmlspecialchars($updatedUser['Username']) . "<br>";
                echo "Email: " . htmlspecialchars($updatedUser['Email']) . "<br>";

                if ($updatedUser['Username'] === $newUsername && $updatedUser['Email'] === $newEmail) {
                    echo "Update Verification: Retrieved data matches expected updated values.<br>";
                } else {
                    echo "Update Verification failed: Retrieved data does not match updated values.<br>";
                }
            } else {
                echo "Failed to retrieve user after update.<br>";
            }
        } else {
            echo "Failed to update user.<br>";
        }

        // Step 5: Clean up by deleting the test user
        $stmt = $db->prepare("DELETE FROM User WHERE UserID = :userID");
        $stmt->bindParam(':userID', $userID);
        $stmt->execute();
        echo "Test user deleted from database.<br>";
    } else {
        echo "Failed to find the created user in the database.<br>";
    }
} else {
    echo "Failed to create user for update test.<br>";
}

