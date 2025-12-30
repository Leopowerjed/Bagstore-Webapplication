<?php
require_once 'includes/IFSConnection.php';

$db = new IFSConnection();
$conn = $db->connect();

function printTable($title, $rows)
{
    echo "\n=== $title ===\n";
    if (empty($rows)) {
        echo "No data found.\n";
        return;
    }
    $headers = array_keys($rows[0]);
    echo implode(" | ", $headers) . "\n";
    echo str_repeat("-", 50) . "\n";
    foreach ($rows as $row) {
        $cleanRow = array_map(function ($val) {
            return substr($val ?? '', 0, 20); }, $row);
        echo implode(" | ", $cleanRow) . "\n";
    }
}

try {
    // Final PR Test
    $sqlPR = "SELECT PART_NO, SUM(QTY_ON_ORDER) as TOTAL_PR 
              FROM IFSAPP.PURCHASE_REQ_LINE_PART 
              WHERE OBJSTATE IN ('Request Created', 'Released', 'Authorized', 'Partially Authorized', 'Planned') 
              AND CONTRACT LIKE 'RM%'
              AND QTY_ON_ORDER > 0
              AND ROWNUM <= 5
              GROUP BY PART_NO";

    echo "Final PR Test...\n";
    printTable("PR Data", $db->fetchAll($db->query($sqlPR)));

    // Final PO Test
    $sqlPO = "SELECT PART_NO, SUM(QTY_ON_ORDER) as TOTAL_PO 
              FROM IFSAPP.PURCHASE_ORDER_LINE_PART 
              WHERE OBJSTATE IN ('Released', 'Confirmed', 'Arrived', 'Received') 
              AND CONTRACT LIKE 'RM%'
              AND QTY_ON_ORDER > 0
              AND ROWNUM <= 5
              GROUP BY PART_NO";

    echo "Final PO Test...\n";
    printTable("PO Data", $db->fetchAll($db->query($sqlPO)));

} catch (Exception $e) {
    echo "Query Error: " . $e->getMessage() . "\n";
}
?>