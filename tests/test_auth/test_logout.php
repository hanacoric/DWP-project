<?php
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/auth.php';

$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$auth = new Auth($db);

$username = "testuser1";
$password = "123";

$auth->login($username, $password);
echo "Testing user logout:<br>";

if($auth->isLoggedIn()) {
    echo "User is logged in.<br>";
    $auth->logout();
}

if(!$auth->isLoggedIn()) {
    echo "User is logged out.<br>";
}else {
    echo "User is still logged in.<br>";
}



