<?php
/**
 * ELK Valuations - UUID Migration (Universal MySQL Compatibility)
 * Correctly adds UUID columns across different MySQL/MariaDB versions.
 */

require_once 'db.php';

try {
    $pdo = DB::getInstance();
    $tables = ['firms', 'users', 'valuations'];

    foreach ($tables as $table) {
        echo "Processing table: $table...\n";

        // Check if uuid column exists manually (since ADD COLUMN IF NOT EXISTS is not standard MySQL)
        $checkColumn = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'uuid'");
        if (!$checkColumn->fetch()) {
            echo "Adding uuid column to $table...\n";
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `uuid` CHAR(36) AFTER `id` ");
            $pdo->exec("CREATE INDEX `idx_{$table}_uuid` ON `$table`(`uuid`) ");
        } else {
            echo "uuid column already exists in $table.\n";
        }

        // 2. Generate UUIDs for existing records that don't have one
        $stmt = $pdo->query("SELECT id FROM `$table` WHERE uuid IS NULL OR uuid = ''");
        $records = $stmt->fetchAll();

        if ($records) {
            echo "Generating UUIDs for " . count($records) . " records in $table...\n";
            $updateStmt = $pdo->prepare("UPDATE `$table` SET uuid = :uuid WHERE id = :id");
            
            foreach ($records as $r) {
                // Generate a version 4 UUID
                $uuid = bin2hex(random_bytes(4)) . '-' . 
                        bin2hex(random_bytes(2)) . '-' . 
                        '4' . substr(bin2hex(random_bytes(2)), 1) . '-' . 
                        bin2hex(random_bytes(2)) . '-' . 
                        bin2hex(random_bytes(6));
                
                $updateStmt->execute(['uuid' => $uuid, 'id' => $r['id']]);
            }
        }
        
        echo "Done with $table.\n\n";
    }

    echo "Migration Complete!\n";

} catch (Exception $e) {
    die("Migration Failed: " . $e->getMessage());
}
