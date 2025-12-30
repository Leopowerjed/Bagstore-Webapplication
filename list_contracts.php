<?php
require_once 'includes/IFSConnection.php';

$db = new IFSConnection();
try {
    $conn = $db->connect();

    echo "=== Contracts with Open PRs ===\n";
    $sqlPR = "SELECT CONTRACT, COUNT(*) as CNT 
              FROM IFSAPP.PURCHASE_REQ_LINE_PART 
              WHERE OBJSTATE IN ('Request Created', 'Released', 'Authorized', 'Partially Authorized', 'Planned')
              GROUP BY CONTRACT";
    $stmtPR = oci_parse($conn, $sqlPR);
    oci_execute($stmtPR);
    while ($row = oci_fetch_array($stmtPR, OCI_ASSOC)) {
        echo $row['CONTRACT'] . ": " . $row['CNT'] . " open PRs\n";
    }

    echo "\n=== Contracts with Open POs ===\n";
    $sqlPO = "SELECT CONTRACT, COUNT(*) as CNT 
              FROM IFSAPP.PURCHASE_ORDER_LINE_PART 
              WHERE OBJSTATE IN ('Released', 'Confirmed', 'Arrived', 'Received')
              GROUP BY CONTRACT";
    $stmtPO = oci_parse($conn, $sqlPO);
    oci_execute($stmtPO);
    while ($row = oci_fetch_array($stmtPO, OCI_ASSOC)) {
        echo $row['CONTRACT'] . ": " . $row['CNT'] . " open POs\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>