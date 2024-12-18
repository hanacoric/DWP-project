<?php
global $db;
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/auth.php';


$auth = new Auth($db);

$username = "testuser1";
$email = "testuser1@example.com";
$password = "123";

echo "Testing user registration:<br>";
if ($auth->register($username, $email, $password)) {
    echo "User registered.<br>";
} else {
    echo "Failed to register user. Username or email may already exist.<br>";
}

