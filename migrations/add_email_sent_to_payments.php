<?php
require_once __DIR__ . '/../php/config.php';

try {
    $conn = getConnection();

    // Check if column exists
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'email_sent_at'");
    $stmt->execute();
    $row = $stmt->fetch();

    if ($row['cnt'] == 0) {
        // Add email_sent_at and sent_to columns
        $conn->exec("ALTER TABLE payments ADD COLUMN email_sent_at DATETIME NULL AFTER created_at, ADD COLUMN sent_to VARCHAR(255) NULL AFTER email_sent_at");
        echo "Migration applied: added 'email_sent_at' and 'sent_to' to 'payments'.\n";
    } else {
        echo "Migration skipped: 'email_sent_at' already exists.\n";
    }

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";
