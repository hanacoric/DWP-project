<?php
global $db;
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/User.php';

$User = new User($db);


$username = "testUserRead";
$email = "testuserread@example.com";
$password = "securePassword123";


echo "Testing createUser for Read Test: ";
$createResult = $User->createUser($username, $email, $password);

if ($createResult) {
    echo "User created successfully.<br>";


    $stmt = $db->prepare("SELECT userID FROM User WHERE Username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $createdUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($createdUser) {
        $userID = $createdUser['userID'];


        echo "Testing getUser: ";
        $retrievedUser = $User->getUser($userID);

        if ($retrievedUser) {
            echo "User retrieved successfully.<br>";
            echo "Username: " . htmlspecialchars($retrievedUser['Username']) . "<br>";
            echo "Email: " . htmlspecialchars($retrievedUser['Email']) . "<br>";


            if ($retrievedUser['Username'] === $username && $retrievedUser['Email'] === $email) {
                echo "Verification: Retrieved data matches expected values.<br>";
            } else {
                echo "Verification failed: Retrieved data does not match.<br>";
            }
        } else {
            echo "Failed to retrieve user.<br>";
        }

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

