<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/IFSConnection.php';

// Category to Part No Mapping
$mapping = [
    'all' => ['25A%'],
    'cement-paper' => ['25A-P%'], // Example sub-patterns if known
    'cement-pp' => ['25A-PP%'],
    'cement-pp-paper' => ['25A-CBT%'],
    'mortar-paper' => ['25A-MP%'],
    'mortar-pp' => ['25A-MPP%'],
    'mortar-film' => ['25A-MF%'],
    'fertilizer' => ['1-220-060%']
];

$category = $_GET['category'] ?? 'all';
if (empty($category))
    $category = 'all';

$db = new IFSConnection();

try {
    $conn = $db->connect();

    $categoryWhere = "";
    $params = [];

    if (isset($mapping[$category])) {
        $patterns = $mapping[$category];
        $conditions = [];
        foreach ($patterns as $index => $pattern) {
            $paramName = "p" . $index;
            $conditions[] = "p.PART_NO LIKE :" . $paramName;
            $params[$paramName] = $pattern;
        }
        $categoryWhere = "AND (" . implode(" OR ", $conditions) . ")";
    }

    $sql = "
        SELECT 
            p.PART_NO, 
            p.DESCRIPTION, 
            NVL(s.TOTAL_ONHAND, 0) as TOTAL_ONHAND,
            NVL(pr.TOTAL_PR, 0) as TOTAL_PR,
            NVL(po.TOTAL_PO, 0) as TOTAL_PO
        FROM IFSAPP.INVENTORY_PART p
        LEFT JOIN (
            SELECT PART_NO, SUM(QTY_ONHAND) as TOTAL_ONHAND 
            FROM IFSAPP.INVENTORY_PART_IN_STOCK 
            WHERE CONTRACT LIKE 'RM%' 
            GROUP BY PART_NO
        ) s ON p.PART_NO = s.PART_NO
        LEFT JOIN (
            SELECT rl.PART_NO, SUM(rl.ORIGINAL_QTY) as TOTAL_PR 
            FROM IFSAPP.PURCHASE_REQ_LINE_ALL rl
            LEFT JOIN IFSAPP.PURCHASE_REQUISITION r ON rl.REQUISITION_NO = r.REQUISITION_NO AND rl.CONTRACT = r.CONTRACT
            WHERE rl.OBJSTATE IN ('Authorized', 'Partially Authorized', 'Planned', 'Released', 'Request Created')
            AND (rl.CONTRACT IN ('RM', 'RMBP', 'RMOR'))
            AND (
                r.REQUISITIONER_CODE LIKE 'ADNU%' OR 
                r.REQUISITIONER_CODE LIKE 'ANTU%' OR 
                r.REQUISITIONER_CODE LIKE 'AUYI%' OR 
                r.REQUISITIONER_CODE LIKE 'NAIN%' OR 
                r.REQUISITIONER_CODE LIKE 'NOVE%' OR 
                r.REQUISITIONER_CODE LIKE 'PHLA%' OR 
                r.REQUISITIONER_CODE LIKE 'PPCH%' OR 
                r.REQUISITIONER_CODE LIKE 'PRDA%' OR 
                r.REQUISITIONER_CODE LIKE 'RURU%' OR 
                r.REQUISITIONER_CODE LIKE 'SAKA%' OR 
                r.REQUISITIONER_CODE LIKE 'SAPU%' OR 
                r.REQUISITIONER_CODE LIKE 'SKTO%' OR 
                r.REQUISITIONER_CODE LIKE 'TRKI%' OR 
                r.REQUISITIONER_CODE LIKE 'WASR%' OR 
                r.REQUISITIONER_CODE LIKE 'WINM%'
            )
            GROUP BY rl.PART_NO
        ) pr ON p.PART_NO = pr.PART_NO
        LEFT JOIN (
            SELECT PART_NO, SUM(NET_PO_QTY) as TOTAL_PO
            FROM (
                -- Case 1: Open POs (Not yet arrived)
                SELECT po.PART_NO, SUM(po.BUY_QTY_DUE) as NET_PO_QTY
                FROM IFSAPP.PURCHASE_ORDER_LINE_ALL po
                INNER JOIN IFSAPP.PURCHASE_REQUISITION r ON po.REQUISITION_NO = r.REQUISITION_NO AND po.CONTRACT = r.CONTRACT
                WHERE po.STATE IN ('Confirmed', 'Released', 'Stopped')
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
                GROUP BY po.PART_NO
                
                UNION ALL
                
                -- Case 2: Arrived/Received POs (Calculate balance from transactions)
                SELECT po.PART_NO, SUM(po.BUY_QTY_DUE - NVL(h.QTY_ARRIVED, 0)) as NET_PO_QTY
                FROM IFSAPP.PURCHASE_ORDER_LINE_ALL po
                INNER JOIN IFSAPP.PURCHASE_REQUISITION r ON po.REQUISITION_NO = r.REQUISITION_NO AND po.CONTRACT = r.CONTRACT
                LEFT JOIN (
                    SELECT ORDER_NO, PART_NO, CONTRACT, SEQUENCE_NO, SUM(QUANTITY) as QTY_ARRIVED
                    FROM IFSAPP.INVENTORY_TRANSACTION_HIST2
                    WHERE TRANSACTION_CODE IN ('ARRIVAL', 'RETWORK', 'UNRCPT-')
                    AND LOCATION_NO IN ('3000', 'BP001', 'OR001')
                    GROUP BY ORDER_NO, PART_NO, CONTRACT, SEQUENCE_NO
                ) h ON po.ORDER_NO = h.ORDER_NO AND po.PART_NO = h.PART_NO AND po.CONTRACT = h.CONTRACT AND po.RELEASE_NO = h.SEQUENCE_NO
                WHERE po.STATE IN ('Arrived', 'Received')
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
                GROUP BY po.PART_NO
            )
            GROUP BY PART_NO
        ) po ON p.PART_NO = po.PART_NO
        WHERE (NVL(s.TOTAL_ONHAND, 0) > 0 OR NVL(pr.TOTAL_PR, 0) > 0 OR NVL(po.TOTAL_PO, 0) > 0)
        $categoryWhere
        ORDER BY p.PART_NO
    ";

    // Global limit for initial view
    $sql = "SELECT * FROM ($sql) WHERE ROWNUM <= 200";

    $stmt = $db->query($sql, $params);
    $data = $db->fetchAll($stmt);

    // Calculate Grand Totals for summary
    $summary = [
        'totalOnhand' => 0,
        'totalPR' => 0,
        'totalPO' => 0,
        'grandTotal' => 0
    ];

    foreach ($data as $row) {
        $onhand = (float) $row['TOTAL_ONHAND'];
        $pr = (float) $row['TOTAL_PR'];
        $po = (float) $row['TOTAL_PO'];

        $summary['totalOnhand'] += $onhand;
        $summary['totalPR'] += $pr;
        $summary['totalPO'] += $po;
    }
    $summary['grandTotal'] = $summary['totalOnhand'] + $summary['totalPR'] + $summary['totalPO'];

    echo json_encode([
        'status' => 'success',
        'count' => count($data),
        'summary' => $summary,
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