<?php
/**
 * ELK Valuations - UUID Migration
 * Adds UUID columns to key tables for secure, unguessable URLs.
 */

require_once 'db.php';

try {
    $pdo = DB::getInstance();

    $tables = ['firms', 'users', 'valuations'];

    foreach ($tables as $table) {
        echo "Processing table: $table...\n";

        // 1. Add uuid column if it doesn't exist
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN IF NOT EXISTS `uuid` CHAR(36) AFTER `id` ");
        $pdo->exec("CREATE INDEX IF NOT EXISTS `idx_{$table}_uuid` ON `$table`(`uuid`) ");

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

        // 3. Make uuid NOT NULL and UNIQUE for future safety
        // Note: We do this after populating existing data
        // $pdo->exec("ALTER TABLE `$table` MODIFY `uuid` CHAR(36) NOT NULL UNIQUE");
        
        echo "Done with $table.\n\n";
    }

    echo "Migration Complete!\n";

} catch (Exception $e) {
    die("Migration Failed: " . $e->getMessage());
}
