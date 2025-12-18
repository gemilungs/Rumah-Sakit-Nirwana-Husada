<?php
require_once __DIR__ . '/../php/config.php';

try {
    $conn = getConnection();

    // Check if column exists
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dokter' AND COLUMN_NAME = 'tarif'");
    $stmt->execute();
    $row = $stmt->fetch();

    if ($row['cnt'] == 0) {
        // Add tarif column
        $conn->exec("ALTER TABLE dokter ADD COLUMN tarif DECIMAL(10,2) NULL DEFAULT NULL AFTER no_telepon");
        echo "Migration applied: added 'tarif' column to 'dokter'.\n";
    } else {
        echo "Migration skipped: 'tarif' column already exists.\n";
    }

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";
