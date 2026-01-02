<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/MySQLConnection.php';

$db = new MySQLConnection();

try {
    $conn = $db->connect();

    $bag_type = $_GET['bag_type'] ?? null;

    $sql = "SELECT id, bag_type, qr_code, part_no, part_desc, quantity, note, delivery_date, created_at 
            FROM MANUAL_DATA";
    $params = [];

    if ($bag_type) {
        $sql .= " WHERE bag_type = ?";
        $params[] = $bag_type;
    }

    $sql .= " ORDER BY created_at DESC LIMIT 100";

    $stmt = $db->query($sql, $params);
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