<?php
/**
 * ELK Valuations - Database Debug
 * Lists saved valuations to confirm the Save feature is working.
 */

require_once 'db.php';

echo "<html><head><title>DB Debug</title><style>body{font-family:sans-serif; background:#0a0a12; color:#fff; padding:40px;} table{width:100%; border-collapse:collapse;} th,td{padding:12px; border:1px solid #333; text-align:left;} th{background:#12121f;}</style></head><body>";
echo "<h1>Saved Valuations</h1>";

try {
    $pdo = DB::getInstance();
    $stmt = $pdo->query("SELECT id, client_name, valuation_mid, created_at FROM valuations ORDER BY created_at DESC");
    
    echo "<table><thead><tr><th>ID</th><th>Client Name</th><th>Valuation Mid</th><th>Created At</th></tr></thead><tbody>";
    
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['client_name']) . "</td>";
        echo "<td>£" . number_format($row['valuation_mid'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
