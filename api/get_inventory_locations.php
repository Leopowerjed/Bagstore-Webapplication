<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/IFSConnection.php';

$part_no = $_GET['part_no'] ?? '';

if (empty($part_no)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing Part No']);
    exit;
}

$db = new IFSConnection();

try {
    $conn = $db->connect();

    $sql = "
        SELECT 
            s.LOCATION_NO, 
            l.LOCATION_NAME,
            SUM(s.QTY_ONHAND) as TOTAL_ONHAND
        FROM IFSAPP.INVENTORY_PART_IN_STOCK s
        LEFT JOIN IFSAPP.INVENTORY_LOCATION l 
            ON s.CONTRACT = l.CONTRACT 
            AND s.LOCATION_NO = l.LOCATION_NO
        WHERE s.PART_NO = :part_no
        AND s.CONTRACT LIKE 'RM%'
        GROUP BY s.LOCATION_NO, l.LOCATION_NAME
        HAVING SUM(s.QTY_ONHAND) > 0
        ORDER BY s.LOCATION_NO
    ";

    $stmt = $db->query($sql, ['part_no' => $part_no]);
    $data = $db->fetchAll($stmt);

    echo json_encode([
        'status' => 'success',
        'part_no' => $part_no,
        'data' => $data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>