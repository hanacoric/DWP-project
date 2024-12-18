<?php
global $db;
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/User.php';


$user = new User($db);


$username = "testUserDelete";
$email = "testuserdelete@example.com";
$password = "securePassword123";


echo "Testing createUser for Delete Test: ";
$createResult = $user->createUser($username, $email, $password);

if ($createResult) {
    echo "User created successfully.<br>";


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


            $deletedUser = $user->getUser($userID);

            if ($deletedUser && $deletedUser['Status'] === 'Blocked') {
                echo "Delete Verification: User status is 'Blocked' as expected.<br>";
            } else {
                echo "Delete Verification failed: User status is not 'Blocked'.<br>";
            }
        } else {
            echo "Failed to mark user as 'Blocked'.<br>";
        }


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
