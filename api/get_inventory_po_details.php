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
        SELECT ORDER_NO, LINE_NO, RELEASE_NO, STATE, BUY_QTY_DUE, BALANCE, ORDER_DATE
        FROM (
            -- Case 1: Open POs (Not yet arrived)
            SELECT po.ORDER_NO, po.LINE_NO, po.RELEASE_NO, po.STATE, po.BUY_QTY_DUE, po.BUY_QTY_DUE as BALANCE, TO_CHAR(o.ORDER_DATE, 'YYYY-MM-DD') as ORDER_DATE
            FROM IFSAPP.PURCHASE_ORDER_LINE_ALL po
            INNER JOIN IFSAPP.PURCHASE_REQUISITION r ON po.REQUISITION_NO = r.REQUISITION_NO AND po.CONTRACT = r.CONTRACT
            INNER JOIN IFSAPP.PURCHASE_ORDER o ON po.ORDER_NO = o.ORDER_NO
            WHERE po.PART_NO = :part_no
            AND po.STATE IN ('Confirmed', 'Released', 'Stopped')
            AND po.CONTRACT IN ('RM', 'RMBP', 'RMOR')
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
            
            UNION ALL
            
            -- Case 2: Arrived/Received POs (Calculate balance from transactions)
            SELECT po.ORDER_NO, po.LINE_NO, po.RELEASE_NO, po.STATE, po.BUY_QTY_DUE, (po.BUY_QTY_DUE - NVL(h.QTY_ARRIVED, 0)) as BALANCE, TO_CHAR(o.ORDER_DATE, 'YYYY-MM-DD') as ORDER_DATE
            FROM IFSAPP.PURCHASE_ORDER_LINE_ALL po
            INNER JOIN IFSAPP.PURCHASE_REQUISITION r ON po.REQUISITION_NO = r.REQUISITION_NO AND po.CONTRACT = r.CONTRACT
            INNER JOIN IFSAPP.PURCHASE_ORDER o ON po.ORDER_NO = o.ORDER_NO
            LEFT JOIN (
                SELECT ORDER_NO, PART_NO, CONTRACT, SEQUENCE_NO, SUM(QUANTITY) as QTY_ARRIVED
                FROM IFSAPP.INVENTORY_TRANSACTION_HIST2
                WHERE TRANSACTION_CODE IN ('ARRIVAL', 'RETWORK', 'UNRCPT-')
                AND LOCATION_NO IN ('3000', 'BP001', 'OR001')
                GROUP BY ORDER_NO, PART_NO, CONTRACT, SEQUENCE_NO
            ) h ON po.ORDER_NO = h.ORDER_NO AND po.PART_NO = h.PART_NO AND po.CONTRACT = h.CONTRACT AND po.RELEASE_NO = h.SEQUENCE_NO
            WHERE po.PART_NO = :part_no2
            AND po.STATE IN ('Arrived', 'Received')
            AND po.CONTRACT IN ('RM', 'RMBP', 'RMOR')
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
        )
        WHERE BALANCE > 0
        ORDER BY ORDER_NO
    ";

    $stmt = $db->query($sql, ['part_no' => $part_no, 'part_no2' => $part_no]);
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