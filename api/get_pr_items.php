<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/IFSConnection.php';

$search_term = $_GET['search_term'] ?? $_GET['requisition_no'] ?? '';

if (empty($search_term)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing Search Term (PR or PO No)']);
    exit;
}

$db = new IFSConnection();

try {
    $conn = $db->connect();

    // Fetch items related to a PR or PO
    $sql = "
        SELECT 
            pl.REQUISITION_NO,
            pl.PART_NO,
            p.DESCRIPTION as PART_DESCRIPTION,
            pl.LINE_NO, 
            pl.RELEASE_NO, 
            pl.STATE as PR_STATE, 
            pl.ORIGINAL_QTY,
            pl.UNIT_MEAS,
            pl.ORDER_NO,
            pol.OBJSTATE as PO_STATE,
            TO_CHAR(r.REQUISITION_DATE, 'YYYY-MM-DD') as REQUISITION_DATE
        FROM IFSAPP.PURCHASE_REQ_LINE_ALL pl
        INNER JOIN IFSAPP.PURCHASE_REQUISITION r ON pl.REQUISITION_NO = r.REQUISITION_NO AND pl.CONTRACT = r.CONTRACT
        LEFT JOIN IFSAPP.PURCHASE_ORDER_LINE_ALL pol ON pl.ORDER_NO = pol.ORDER_NO 
            AND pl.PART_NO = pol.PART_NO 
            AND pl.CONTRACT = pol.CONTRACT
        LEFT JOIN IFSAPP.INVENTORY_PART p ON pl.PART_NO = p.PART_NO AND pl.CONTRACT = p.CONTRACT
        WHERE (pl.REQUISITION_NO = :search OR pl.ORDER_NO = :search)
        AND pl.CONTRACT IN ('RM', 'RMBP', 'RMOR')
        ORDER BY pl.REQUISITION_NO, pl.LINE_NO, pl.RELEASE_NO
    ";

    $stmt = $db->query($sql, ['search' => $search_term]);
    $data = $db->fetchAll($stmt);

    echo json_encode([
        'status' => 'success',
        'search_term' => $search_term,
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