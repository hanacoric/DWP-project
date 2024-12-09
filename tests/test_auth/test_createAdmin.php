<?php
global $db;
session_start();
require_once '../../src/includes/db.php';
require_once '../../src/classes/auth.php';

$auth = new Auth($db);

$username = 'admin';
$envFile = __DIR__ . '/../../.env';
if (!file_exists($envFile)) {
    die("Error: .env file not found $envFile");
}
$env = parse_ini_file($envFile);
$password = $env['ADMIN_PASSWORD'] ?? die("Error: ADMIN_PASSWORD not set in .env");


if ($auth->login($username, $password)) {
    echo "Login successful.";

    $stmt = $db->prepare("SELECT Role.RoleName FROM User JOIN Role ON User.RoleID = Role.RoleID WHERE User.Username = :username");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $role = $stmt->fetch(PDO::FETCH_ASSOC)['RoleName'];

    if ($role === 'Admin') {
        echo "User '$username' is an Admin.";
    } else {
        echo "User '$username' is NOT an Admin.";
    }
} else {
    echo "Login failed.";
}


