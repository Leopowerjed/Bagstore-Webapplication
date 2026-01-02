<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/IFSConnection.php';
require_once __DIR__ . '/../includes/MySQLConnection.php';

// Category to EAN_NO Prefix Mapping
$mapping = [
    '11' => '11%',
    '21' => '21%',
    '23' => '23%',
    '13' => '13%',
    '43' => '43%',
    '52' => '52%',
    '32' => '32%',
    '22' => '22%',
    '33' => '33%'
];

$category = $_GET['category'] ?? '11';
if (empty($category) || !isset($mapping[$category])) {
    $category = '11';
}

$ifs = new IFSConnection();
$mysql = new MySQLConnection();

try {
    $ifs_conn = $ifs->connect();

    // 1. Fetch Manual Data from MySQL
    $manual_data = [];
    try {
        $mysql_conn = $mysql->connect();
        $stmt_manual = $mysql->query("
            SELECT 
                part_no, 
                data_type, 
                CASE WHEN (order_no IS NOT NULL AND order_no != '') THEN 1 ELSE 0 END as has_po,
                SUM(quantity) as total_qty 
            FROM MANUAL_DATA 
            GROUP BY part_no, data_type, CASE WHEN (order_no IS NOT NULL AND order_no != '') THEN 1 ELSE 0 END
        ");
        $manual_rows = $mysql->fetchAll($stmt_manual);

        foreach ($manual_rows as $m_row) {
            $p = $m_row['part_no'];
            if (!isset($manual_data[$p])) {
                $manual_data[$p] = [
                    'manual_receipt_pr' => 0,
                    'manual_receipt_po' => 0,
                    'manual_issue' => 0
                ];
            }

            if ($m_row['data_type'] === 'RECEIPT') {
                if ($m_row['has_po'] == 1) {
                    $manual_data[$p]['manual_receipt_po'] += (float) $m_row['total_qty'];
                } else {
                    $manual_data[$p]['manual_receipt_pr'] += (float) $m_row['total_qty'];
                }
            } elseif ($m_row['data_type'] === 'ISSUE') {
                $manual_data[$p]['manual_issue'] += (float) $m_row['total_qty'];
            }
        }
    } catch (Exception $me) {
        // Fallback or log error
        error_log("MySQL Manual Data Error: " . $me->getMessage());
    }

    // Default part no filter for all bag categories
    $categoryWhere = "AND p.PART_NO LIKE '25A%' AND p.EAN_NO LIKE :ean";
    $params = ['ean' => $mapping[$category]];

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
        ORDER BY p.EAN_NO
    ";

    // Global limit for initial view
    $sql_paginated = "SELECT * FROM ($sql) WHERE ROWNUM <= 200";

    $stmt = $ifs->query($sql_paginated, $params);
    $ifs_data = $ifs->fetchAll($stmt);

    $processed_data = [];
    foreach ($ifs_data as $row) {
        $p = $row['PART_NO'];
        $m = $manual_data[$p] ?? ['manual_receipt_pr' => 0, 'manual_receipt_po' => 0, 'manual_issue' => 0];

        $row['MANUAL_RECEIPT_PR'] = (float) $m['manual_receipt_pr'];
        $row['MANUAL_RECEIPT_PO'] = (float) $m['manual_receipt_po'];
        $row['MANUAL_ISSUE'] = (float) $m['manual_issue'];

        // Remove from manual list once matched so we can see what's left
        unset($manual_data[$p]);

        $processed_data[] = $row;
    }

    // Add manual-only items (if any match the category prefix indirectly)
    // We only include remaining manual items if they match the CURRENT category's EAN prefix
    if (!empty($manual_data)) {
        $manual_part_nos = array_keys($manual_data);

        // Build a query to check matching parts in IFS for the current category
        $part_placeholders = implode(',', array_fill(0, count($manual_part_nos), '?'));
        // Note: Oracle uses :p1, :p2 format or we can use positional if library supports it.
        // Our IFSConnection seems to use named parameters. Let's use a simpler approach.

        // Fetch only those parts from the unmatched manual list that belong to this category
        $sql_lookup = "
            SELECT PART_NO, DESCRIPTION 
            FROM IFSAPP.INVENTORY_PART 
            WHERE PART_NO LIKE '25A%' 
            AND EAN_NO LIKE :ean 
            AND PART_NO IN (
        ";

        // Handle IN clause manually for simplicity in this context
        $lookup_params = ['ean' => $mapping[$category]];
        $i = 1;
        $in_clause = [];
        foreach ($manual_part_nos as $mp) {
            $key = 'p' . $i++;
            $in_clause[] = ':' . $key;
            $lookup_params[$key] = $mp;
        }
        $sql_lookup .= implode(',', $in_clause) . ")";

        try {
            $stmt_lookup = $ifs->query($sql_lookup, $lookup_params);
            $matched_manual_parts = $ifs->fetchAll($stmt_lookup);

            foreach ($matched_manual_parts as $matched) {
                $p = $matched['PART_NO'];
                $m = $manual_data[$p];
                $processed_data[] = [
                    'PART_NO' => $p,
                    'DESCRIPTION' => $matched['DESCRIPTION'] ?? 'Manual Recorded Item',
                    'TOTAL_ONHAND' => 0,
                    'TOTAL_PR' => 0,
                    'TOTAL_PO' => 0,
                    'MANUAL_RECEIPT_PR' => (float) $m['manual_receipt_pr'],
                    'MANUAL_RECEIPT_PO' => (float) $m['manual_receipt_po'],
                    'MANUAL_ISSUE' => (float) $m['manual_issue']
                ];
            }
        } catch (Exception $e_lookup) {
            // If lookup fails, we skip manual-only items to avoid polluting other categories
            error_log("Manual-only Lookup Error: " . $e_lookup->getMessage());
        }
    }

    // Calculate Grand Totals based on current visibility (default summary using IFS only for now, 
    // or let JS calculate summary based on toggle)
    echo json_encode([
        'status' => 'success',
        'count' => count($processed_data),
        'data' => $processed_data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>