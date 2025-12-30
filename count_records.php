<?php
require_once 'includes/IFSConnection.php';

$db = new IFSConnection();
try {
    $conn = $db->connect();

    $views = ['PURCHASE_REQ_LINE_PART', 'PURCHASE_ORDER_LINE_PART', 'PURCHASE_REQ_LINE', 'PURCHASE_ORDER_LINE'];

    foreach ($views as $view) {
        $sql = "SELECT COUNT(*) as TOTAL FROM IFSAPP.$view";
        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_array($stmt, OCI_ASSOC);
        echo "View $view: " . $row['TOTAL'] . " records\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>