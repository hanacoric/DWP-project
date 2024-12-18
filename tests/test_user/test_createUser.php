<?php
global $db;
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/User.php';

$User = new User($db);

$username = "testUser";
$email = "testuser@example.com";
$password = "securePassword123";


echo "Testing createUser: ";
$createResult = $User->createUser($username, $email, $password);

if ($createResult) {
    echo "User created successfully.<br>";
} else {
    echo "Failed to create user.<br>";
}


try {
    $stmt = $db->prepare("SELECT * FROM User WHERE Username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $createdUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($createdUser) {
        echo "Verification: User found in database.<br>";
        echo "Username: " . htmlspecialchars($createdUser['Username']) . "<br>";
        echo "Email: " . htmlspecialchars($createdUser['Email']) . "<br>";
    } else {
        echo "Verification failed: User not found in database.<br>";
    }
} catch (PDOException $e) {
    echo "Error verifying user: " . $e->getMessage();
}


$stmt = $db->prepare("DELETE FROM User WHERE Username = :username");
$stmt->bindParam(':username', $username);
$stmt->execute();

echo "Test user deleted from database.<br>";
