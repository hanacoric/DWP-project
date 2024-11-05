<?php

require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/classes/user.php';

$db = new PDO("mysql:host=localhost;port=3306;dbname=SemesterProjectDB", "hana", "123456");

$user = new User($db);

function printResult($testName, $result)
{
    echo $testName . ": " . ($result ? "Passed" : "Failed") . "<br>";
}

//test user creation
$createResult = $user->createUser("testuser", "testuser@example.com", "securePassword123");
printResult("Create User", $createResult);




