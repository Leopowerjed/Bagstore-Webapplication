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
            pl.REQUISITION_NO, 
            pl.LINE_NO, 
            pl.RELEASE_NO, 
            pl.STATE, 
            pl.ORIGINAL_QTY,
            TO_CHAR(r.REQUISITION_DATE, 'YYYY-MM-DD') as REQUISITION_DATE
        FROM IFSAPP.PURCHASE_REQ_LINE_ALL pl
        INNER JOIN IFSAPP.PURCHASE_REQUISITION r ON pl.REQUISITION_NO = r.REQUISITION_NO AND pl.CONTRACT = r.CONTRACT
        WHERE pl.PART_NO = :part_no
        AND pl.STATE NOT IN ('PO Created', 'RequestCreated', 'Stopped')
        AND pl.CONTRACT IN ('RM', 'RMBP', 'RMOR')
        AND (
            r.REQUISITIONER_CODE LIKE 'ADNU%' OR r.REQUISITIONER_CODE LIKE 'ANTU%' OR 
            r.REQUISITIONER_CODE LIKE 'AUYI%' OR r.REQUISITIONER_CODE LIKE 'NAIN%' OR 
            r.REQUISITIONER_CODE LIKE 'NOVE%' OR r.REQUISITIONER_CODE LIKE 'PHLA%' OR 
            r.REQUISITIONER_CODE LIKE 'PPCH%' OR r.REQUISITIONER_CODE LIKE 'PRDA%' OR 
            r.REQUISITIONER_CODE LIKE 'RURU%' OR r.REQUISITIONER_CODE LIKE 'SAKA%' OR 
            r.REQUISITIONER_CODE LIKE 'SAPU%' OR r.REQUISITIONER_CODE LIKE 'SKTO%' OR 
            r.REQUISITIONER_CODE LIKE 'TRKI%' OR r.REQUISITIONER_CODE LIKE 'WASR%' OR 
            r.REQUISITIONER_CODE LIKE 'WINM%'
        )
        ORDER BY pl.REQUISITION_NO, pl.LINE_NO
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