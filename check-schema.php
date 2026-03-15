<?php
require_once 'db.php';
try {
    $pdo = DB::getInstance();
    $stmt = $pdo->query("DESCRIBE valuations");
    while ($row = $stmt->fetch()) {
        print_r($row);
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
