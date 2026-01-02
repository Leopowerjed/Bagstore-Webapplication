<?php
require_once __DIR__ . '/includes/MySQLConnection.php';

$db = new MySQLConnection();

try {
    $conn = $db->connect();

    echo "Fixing constraints for MANUAL_DATA table...\n";

    // Drop part_no constraint
    $sql1 = "ALTER TABLE MANUAL_DATA DROP FOREIGN KEY FK_MANUAL_DATA_part_no";
    if ($conn->query($sql1)) {
        echo "Dropped FK_MANUAL_DATA_part_no successfully.\n";
    } else {
        echo "Note: Could not drop FK_MANUAL_DATA_part_no (It might not exist): " . $conn->error . "\n";
    }

    // Drop bag_type constraint
    $sql2 = "ALTER TABLE MANUAL_DATA DROP FOREIGN KEY FK_MANUAL_DATA_bag_type";
    if ($conn->query($sql2)) {
        echo "Dropped FK_MANUAL_DATA_bag_type successfully.\n";
    } else {
        echo "Note: Could not drop FK_MANUAL_DATA_bag_type (It might not exist): " . $conn->error . "\n";
    }

    echo "Constraint fix completed.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>