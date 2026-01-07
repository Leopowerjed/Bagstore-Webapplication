<?php
require_once __DIR__ . '/includes/IFSConnection.php';
$db = new IFSConnection();
try {
    $conn = $db->connect();
    $sql = "SELECT table_name FROM all_tab_comments WHERE table_name = 'PURCHASE_RECEIPT_NEW'";
    $stmt = $db->query($sql);
    $result = $db->fetchAll($stmt);
    if (!empty($result)) {
        echo "Found: PURCHASE_RECEIPT_NEW\n";
        $sql_cols = "SELECT column_name FROM all_tab_columns WHERE table_name = 'PURCHASE_RECEIPT_NEW' AND owner = 'IFSAPP'";
        $stmt_cols = $db->query($sql_cols);
        $cols = $db->fetchAll($stmt_cols);
        print_r($cols);
    } else {
        echo "NOT Found: PURCHASE_RECEIPT_NEW\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
