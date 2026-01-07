<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/MySQLConnection.php';

$db = new MySQLConnection();

try {
    $conn = $db->connect();

    $sql = "SELECT id, data_type, requisition_no, order_no, po_state, line_no, release_no, bag_type, qr_code, part_no, part_desc, quantity, note, delivery_date, created_at, archived_at 
            FROM MANUAL_DATA_HISTORY 
            ORDER BY archived_at DESC LIMIT 200";

    $stmt = $db->query($sql);
    $data = $db->fetchAll($stmt);

    echo json_encode([
        'status' => 'success',
        'count' => count($data),
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