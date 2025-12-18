<?php
require_once __DIR__ . '/../php/config.php';

try {
    $conn = getConnection();

    // Create payments table if not exists
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments'");
    $stmt->execute();
    $row = $stmt->fetch();

    if ($row['cnt'] == 0) {
        $sql = "CREATE TABLE payments (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            transaction_no VARCHAR(100) DEFAULT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            method VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            INDEX (booking_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->exec($sql);
        echo "Migration applied: created 'payments' table.\n";
    } else {
        echo "Migration skipped: 'payments' table already exists.\n";
    }

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";
