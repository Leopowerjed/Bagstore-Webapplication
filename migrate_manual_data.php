<?php
require_once __DIR__ . '/includes/MySQLConnection.php';

$db = new MySQLConnection();

try {
    $conn = $db->connect();

    $sql = "
        ALTER TABLE MANUAL_DATA 
        ADD COLUMN data_type ENUM('RECEIPT', 'ISSUE') DEFAULT 'RECEIPT' AFTER id,
        ADD COLUMN requisition_no VARCHAR(50) AFTER data_type,
        ADD COLUMN line_no VARCHAR(10) AFTER requisition_no,
        ADD COLUMN release_no VARCHAR(10) AFTER line_no;
    ";

    if ($conn->query($sql)) {
        echo "Table MANUAL_DATA updated successfully.";
    } else {
        echo "Error updating table: " . $conn->error;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>