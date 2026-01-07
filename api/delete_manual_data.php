<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/MySQLConnection.php';

$db = new MySQLConnection();

try {
    $conn = $db->connect();

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'POST' && $method !== 'DELETE') {
        throw new Exception("Method not allowed");
    }

    // Support both DELETE method or POST with id
    $input = json_decode(file_get_contents('php://input'), true);

    // If not in body, check query params for DELETE requests
    $id = $input['id'] ?? ($_GET['id'] ?? null);

    if (!$id) {
        throw new Exception("Missing record ID");
    }

    $sql = "DELETE FROM MANUAL_DATA WHERE id = ?";
    $stmt = $db->query($sql, [$id]);

    if ($conn->affected_rows > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Record deleted successfully'
        ]);
    } else {
        throw new Exception("Record not found or already deleted");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>