<?php
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/auth.php';

$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$auth = new Auth($db);

$username = "testuser1";
$password = "123";

if ($auth->login($username, $password)) {
    echo "Login successful.<br>";
} else {
    echo "Failed to log in.<br>";
}

if ($auth->login($username, "wrongPassword")) {
    echo "Unexpectedly logged in with incorrect password.<br>";
} else {
    echo "The system blocked login with an incorrect password<br>";
}
