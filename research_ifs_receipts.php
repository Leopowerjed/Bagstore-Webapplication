<?php
require_once __DIR__ . '/includes/IFSConnection.php';

$db = new IFSConnection();

try {
    $conn = $db->connect();

    // Check columns for PURCHASE_RECEIPT
    $sql = "SELECT column_name FROM all_tab_columns WHERE table_name = 'PURCHASE_RECEIPT' AND owner = 'IFSAPP'";
    $stmt = $db->query($sql);
    $columns = $db->fetchAll($stmt);

    echo "Columns in IFSAPP.PURCHASE_RECEIPT:\n";
    foreach ($columns as $col) {
        echo $col['COLUMN_NAME'] . "\n";
    }

    // Also check some sample data if any
    echo "\nSample Data (Top 5):\n";
    $sql_sample = "SELECT * FROM (SELECT ORDER_NO, PART_NO, SENDER_RECEIPT_REF, DELIVERY_NOTE_NO FROM IFSAPP.PURCHASE_RECEIPT ORDER BY ARRIVAL_DATE DESC) WHERE ROWNUM <= 5";
    $stmt_sample = $db->query($sql_sample);
    $samples = $db->fetchAll($stmt_sample);
    print_r($samples);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>