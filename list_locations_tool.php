<?php
require_once __DIR__ . '/includes/IFSConnection.php';

$db = new IFSConnection();

try {
    $conn = $db->connect();

    // Query to get locations where Cement Bag parts (25A%) are stored
    // Filtering by PART_NO LIKE '25A%' and only locations with QTY_ONHAND > 0
    $sql = "
        SELECT DISTINCT 
            s.CONTRACT, 
            s.LOCATION_NO, 
            l.LOCATION_NAME
        FROM IFSAPP.INVENTORY_PART_IN_STOCK s
        INNER JOIN IFSAPP.INVENTORY_PART p ON s.PART_NO = p.PART_NO
        LEFT JOIN IFSAPP.INVENTORY_LOCATION l 
            ON s.CONTRACT = l.CONTRACT 
            AND s.LOCATION_NO = l.LOCATION_NO
        WHERE p.PART_NO LIKE '25A%'
        AND s.QTY_ONHAND > 0
        AND s.CONTRACT LIKE 'RM%'
        ORDER BY s.CONTRACT, s.LOCATION_NO
    ";

    $stmt = $db->query($sql);
    $data = $db->fetchAll($stmt);

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>