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

    // Get headers
    $headers = array_keys($rows[0]);
    echo implode(" | ", $headers) . "\n";
    echo str_repeat("-", 50) . "\n";

    foreach ($rows as $row) {
        // Truncate long fields for display
        $cleanRow = array_map(function ($val) {
            return substr($val ?? '', 0, 20);
        }, $row);
        echo implode(" | ", $cleanRow) . "\n";
    }
    echo "\n";
}

try {
    // Test 1: Inventory Part (General info)
    // Try with IFSAPP schema prefix
    $sql1 = "SELECT PART_NO, DESCRIPTION, TYPE_CODE 
             FROM IFSAPP.INVENTORY_PART 
             WHERE ROWNUM <= 5";

    echo "Querying IFSAPP.INVENTORY_PART...\n";
    $stmt1 = $db->query($sql1);
    $rows1 = $db->fetchAll($stmt1);
    printTable("Inventory Parts (Top 5)", $rows1);

    // Test 2: Inventory In Stock (Location info)
    $sql2 = "SELECT PART_NO, CONTRACT, LOCATION_NO, QTY_ONHAND 
             FROM IFSAPP.INVENTORY_PART_IN_STOCK 
             WHERE ROWNUM <= 5";

    echo "Querying IFSAPP.INVENTORY_PART_IN_STOCK...\n";
    $stmt2 = $db->query($sql2);
    $rows2 = $db->fetchAll($stmt2);
    printTable("Stock on Hand (Top 5)", $rows2);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>