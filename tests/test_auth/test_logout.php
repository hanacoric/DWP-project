<?php
global $db;
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/auth.php';


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



