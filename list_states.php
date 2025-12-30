<?php
require_once 'includes/IFSConnection.php';

$db = new IFSConnection();
try {
    $conn = $db->connect();

    echo "=== States in PURCHASE_REQ_LINE_PART ===\n";
    $sqlPR = "SELECT OBJSTATE, COUNT(*) as CNT FROM IFSAPP.PURCHASE_REQ_LINE_PART GROUP BY OBJSTATE";
    $stmtPR = oci_parse($conn, $sqlPR);
    oci_execute($stmtPR);
    while ($row = oci_fetch_array($stmtPR, OCI_ASSOC)) {
        echo $row['OBJSTATE'] . ": " . $row['CNT'] . "\n";
    }

    echo "\n=== States in PURCHASE_ORDER_LINE_PART ===\n";
    $sqlPO = "SELECT OBJSTATE, COUNT(*) as CNT FROM IFSAPP.PURCHASE_ORDER_LINE_PART GROUP BY OBJSTATE";
    $stmtPO = oci_parse($conn, $sqlPO);
    oci_execute($stmtPO);
    while ($row = oci_fetch_array($stmtPO, OCI_ASSOC)) {
        echo $row['OBJSTATE'] . ": " . $row['CNT'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>