<?php
require_once __DIR__ . '/includes/MySQLConnection.php';
$db = new MySQLConnection();

try {
    $conn = $db->connect();

    // Create History table with same structure plus archived_at
    $sql = "CREATE TABLE IF NOT EXISTS MANUAL_DATA_HISTORY (
        id INT PRIMARY KEY,
        data_type VARCHAR(20),
        requisition_no VARCHAR(50),
        order_no VARCHAR(50),
        po_state VARCHAR(50),
        line_no VARCHAR(50),
        release_no VARCHAR(50),
        bag_type VARCHAR(50),
        qr_code VARCHAR(255),
        part_no VARCHAR(50),
        part_desc TEXT,
        quantity DECIMAL(12,2),
        note TEXT,
        delivery_date DATE,
        created_at DATETIME,
        archived_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    $db->query($sql);
    echo "MANUAL_DATA_HISTORY table created/verified successfully.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>