<?php
global $db;
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/user.php';


$user = new User($db);

function printResult($testName, $result)
{
    echo $testName . ": " . ($result ? "Passed" : "Failed") . "<br>";
}

$createResult = $user->createUser("testuser", "testuser@example.com", "securePassword123");
printResult("Create User", $createResult);




