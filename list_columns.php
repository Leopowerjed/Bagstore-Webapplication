<?php
require_once 'includes/IFSConnection.php';

function listColumns($view)
{
    echo "\n=== Columns for $view ===\n";
    $db = new IFSConnection();
    try {
        $conn = $db->connect();
        $sql = "SELECT COLUMN_NAME, DATA_TYPE FROM ALL_TAB_COLUMNS WHERE TABLE_NAME = '$view' AND OWNER = 'IFSAPP'";
        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);
        while ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
            echo $row['COLUMN_NAME'] . " (" . $row['DATA_TYPE'] . ")\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

listColumns('PURCHASE_REQ_LINE_PART');
listColumns('PURCHASE_ORDER_LINE_PART');
?>