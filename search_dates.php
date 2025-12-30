<?php
require_once 'includes/IFSConnection.php';

$db = new IFSConnection();
try {
    $conn = $db->connect();

    echo "=== Columns in PURCHASE_REQUISITION ===\n";
    $sqlPr = "SELECT COLUMN_NAME FROM ALL_TAB_COLUMNS WHERE TABLE_NAME = 'PURCHASE_REQUISITION' AND OWNER = 'IFSAPP'";
    $stmtPr = oci_parse($conn, $sqlPr);
    oci_execute($stmtPr);
    while ($col = oci_fetch_array($stmtPr, OCI_ASSOC)) {
        if (strpos($col['COLUMN_NAME'], 'DATE') !== false) {
            echo $col['COLUMN_NAME'] . ", ";
        }
    }
    echo "\n";

    echo "\n=== Columns in PURCHASE_ORDER ===\n";
    $sqlPo = "SELECT COLUMN_NAME FROM ALL_TAB_COLUMNS WHERE TABLE_NAME = 'PURCHASE_ORDER' AND OWNER = 'IFSAPP'";
    $stmtPo = oci_parse($conn, $sqlPo);
    oci_execute($stmtPo);
    while ($col = oci_fetch_array($stmtPo, OCI_ASSOC)) {
        if (strpos($col['COLUMN_NAME'], 'DATE') !== false) {
            echo $col['COLUMN_NAME'] . ", ";
        }
    }
    echo "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>