<?php
global $db;
require_once __DIR__ . '/../../src/includes/db.php';

try {
    $query = $db->query("SELECT DATABASE()");
    $result = $query->fetch(PDO::FETCH_ASSOC);
    echo "Connected to database: " . $result['DATABASE()'];
} catch (PDOException $e) {
    echo "Test failed: " . $e->getMessage();
}


