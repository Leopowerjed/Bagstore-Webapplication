<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/IFSConnection.php';

$requisition_no = $_GET['requisition_no'] ?? '';

if (empty($requisition_no)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing Requisition No']);
    exit;
}

$db = new IFSConnection();

try {
    $conn = $db->connect();

    // Fetch items related to a PR
    $sql = "
        SELECT 
            pl.PART_NO,
            p.DESCRIPTION as PART_DESCRIPTION,
            pl.LINE_NO, 
            pl.RELEASE_NO, 
            pl.STATE, 
            pl.ORIGINAL_QTY,
            pl.UNIT_MEAS,
            TO_CHAR(r.REQUISITION_DATE, 'YYYY-MM-DD') as REQUISITION_DATE,
            r.REQUISITIONER_CODE
        FROM IFSAPP.PURCHASE_REQ_LINE_ALL pl
        INNER JOIN IFSAPP.PURCHASE_REQUISITION r ON pl.REQUISITION_NO = r.REQUISITION_NO AND pl.CONTRACT = r.CONTRACT
        LEFT JOIN IFSAPP.INVENTORY_PART p ON pl.PART_NO = p.PART_NO AND pl.CONTRACT = p.CONTRACT
        WHERE pl.REQUISITION_NO = :req_no
        AND pl.CONTRACT IN ('RM', 'RMBP', 'RMOR')
        ORDER BY pl.LINE_NO, pl.RELEASE_NO
    ";

    $stmt = $db->query($sql, ['req_no' => $requisition_no]);
    $data = $db->fetchAll($stmt);

    echo json_encode([
        'status' => 'success',
        'requisition_no' => $requisition_no,
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