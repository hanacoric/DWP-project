<?php
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/User.php';

// Initialize the database connection
$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize the User object
$User = new User($db);

// Test Data
$username = "testUserRead";
$email = "testuserread@example.com";
$password = "securePassword123";

// Step 1: Create a test user
echo "Testing createUser for Read Test: ";
$createResult = $User->createUser($username, $email, $password);

if ($createResult) {
    echo "User created successfully.<br>";

    // Step 2: Retrieve the user by ID
    // Find the user ID for the created user
    $stmt = $db->prepare("SELECT userID FROM User WHERE Username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $createdUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($createdUser) {
        $userID = $createdUser['userID'];

        // Test the getUser method
        echo "Testing getUser: ";
        $retrievedUser = $User->getUser($userID);

        if ($retrievedUser) {
            echo "User retrieved successfully.<br>";
            echo "Username: " . htmlspecialchars($retrievedUser['Username']) . "<br>";
            echo "Email: " . htmlspecialchars($retrievedUser['Email']) . "<br>";

            // Verify that the retrieved data matches the created user data
            if ($retrievedUser['Username'] === $username && $retrievedUser['Email'] === $email) {
                echo "Verification: Retrieved data matches expected values.<br>";
            } else {
                echo "Verification failed: Retrieved data does not match.<br>";
            }
        } else {
            echo "Failed to retrieve user.<br>";
        }

        // Step 3: Clean up by deleting the test user
        $stmt = $db->prepare("DELETE FROM User WHERE userID = :userID");
        $stmt->bindParam(':userID', $userID);
        $stmt->execute();
        echo "Test user deleted from database.<br>";
    } else {
        echo "Failed to find the created user in the database.<br>";
    }
} else {
    echo "Failed to create user for read test.<br>";
}

