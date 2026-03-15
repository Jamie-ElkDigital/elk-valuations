<?php
require_once 'db.php';
try {
    $pdo = DB::getInstance();
    
    // Check if business_desc exists
    $columns = $pdo->query("SHOW COLUMNS FROM valuations")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('business_desc', $columns)) {
        $pdo->exec("ALTER TABLE valuations ADD COLUMN business_desc TEXT AFTER report_date");
        echo "Added business_desc column.\n";
    } else {
        echo "business_desc column already exists.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
