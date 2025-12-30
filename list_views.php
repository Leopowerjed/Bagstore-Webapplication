<?php
require_once 'includes/IFSConnection.php';

$db = new IFSConnection();
try {
    $conn = $db->connect();
    $sql = "SELECT OWNER, TABLE_NAME 
            FROM ALL_TAB_COLUMNS 
            WHERE TABLE_NAME LIKE 'PURCHASE_%' 
            AND OWNER = 'IFSAPP'
            GROUP BY OWNER, TABLE_NAME
            ORDER BY TABLE_NAME";
    $stmt = oci_parse($conn, $sql);
    oci_execute($stmt);
    echo "=== Purchase Related Views (IFSAPP) ===\n";
    while ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
        echo $row['TABLE_NAME'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>