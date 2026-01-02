<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/MySQLConnection.php';

$db = new MySQLConnection();

try {
    $conn = $db->connect();

    // Check if table exists, if not, schema.sql should have been run but let's be safe
    // For this implementation, we assume the table is created via phpMyAdmin as per schema.sql

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'POST') {
        throw new Exception("Only POST method allowed");
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception("Invalid input data");
    }

    $data_type = $input['data_type'] ?? 'RECEIPT';
    $bag_type = $input['bag_type'] ?? null;
    $qr_code = $input['qr_code'] ?? null;
    $part_no = $input['part_no'] ?? null;
    $part_desc = $input['part_desc'] ?? null;
    $quantity = $input['quantity'] ?? 0;
    $note = $input['note'] ?? '';
    $delivery_date = $input['delivery_date'] ?? date('Y-m-d');
    $requisition_no = $input['requisition_no'] ?? null;
    $line_no = $input['line_no'] ?? null;
    $release_no = $input['release_no'] ?? null;

    $sql = "INSERT INTO MANUAL_DATA (data_type, requisition_no, line_no, release_no, bag_type, qr_code, part_no, part_desc, quantity, note, delivery_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $params = [$data_type, $requisition_no, $line_no, $release_no, $bag_type, $qr_code, $part_no, $part_desc, $quantity, $note, $delivery_date];

    $stmt = $db->query($sql, $params);

    echo json_encode([
        'status' => 'success',
        'message' => 'Data saved successfully',
        'id' => $conn->insert_id
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>