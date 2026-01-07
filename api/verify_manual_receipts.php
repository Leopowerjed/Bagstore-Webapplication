<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/IFSConnection.php';
require_once __DIR__ . '/../includes/MySQLConnection.php';

$ifs = new IFSConnection();
$mysql = new MySQLConnection();

try {
    $mysql_conn = $mysql->connect();
    $ifs_conn = $ifs->connect();

    $action = $_GET['action'] ?? 'verify'; // verify or clean

    // 1. Fetch all manual receipts that have PO/PR info and a Ref (note)
    $sql_manual = "SELECT id, requisition_no, order_no, line_no, release_no, part_no, note, quantity 
                   FROM MANUAL_DATA 
                   WHERE data_type = 'RECEIPT' 
                   AND ( (order_no IS NOT NULL AND order_no != '') OR (requisition_no IS NOT NULL AND requisition_no != '') )
                   AND (note IS NOT NULL AND note != '')";

    $stmt_manual = $mysql->query($sql_manual);
    $manual_entries = $mysql->fetchAll($stmt_manual);

    if (empty($manual_entries)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'No manual receipts found with Ref number.',
            'matches' => []
        ]);
        exit;
    }

    $matches = [];
    $matched_ids = [];

    // 2. For each manual entry, check IFS for matching receipt
    foreach ($manual_entries as $entry) {
        $bill_number = trim($entry['note']);

        // Query IFS for matching receipt
        // Condition 1: PR or PO No match
        // Condition 2: Part No match
        // Condition 3: Quantity match
        // Condition 4: Receipt Reference (Ref) match

        // Note: Using PURCHASE_RECEIPT_NEW which already contains REQUISITION_NO
        $sql_ifs = "SELECT RECEIPT_NO, ARRIVAL_DATE, QTY_ARRIVED, ORDER_NO 
                    FROM IFSAPP.PURCHASE_RECEIPT_NEW
                    WHERE PART_NO = :part_no 
                    AND RECEIPT_REFERENCE = :ref
                    AND QTY_ARRIVED = :qty
                    AND (ORDER_NO = :order_no OR REQUISITION_NO = :pr_no)";

        $ifs_params = [
            'part_no' => $entry['part_no'],
            'ref' => $bill_number,
            'qty' => $entry['quantity'],
            'order_no' => $entry['order_no'] ?? '',
            'pr_no' => $entry['requisition_no'] ?? ''
        ];

        try {
            $stmt_ifs = $ifs->query($sql_ifs, $ifs_params);
            $ifs_receipt = $ifs->fetchAll($stmt_ifs);

            if (!empty($ifs_receipt)) {
                $matches[] = [
                    'manual_id' => $entry['id'],
                    'order_no' => $entry['order_no'],
                    'part_no' => $entry['part_no'],
                    'ref' => $bill_number,
                    'ifs_receipt_no' => $ifs_receipt[0]['RECEIPT_NO'],
                    'ifs_qty' => $ifs_receipt[0]['QTY_ARRIVED'],
                    'ifs_date' => $ifs_receipt[0]['ARRIVAL_DATE']
                ];
                $matched_ids[] = $entry['id'];
            }
        } catch (Exception $e_ifs) {
            // Log error for specific entry but continue processing others
            error_log("IFS Verification Error for ID " . $entry['id'] . ": " . $e_ifs->getMessage());
        }
    }

    // 3. If action is 'clean' and we have matches
    if ($action === 'clean' && !empty($matched_ids)) {
        // Prepare placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($matched_ids), '?'));

        // Move to History Table FIRST
        $sql_move = "INSERT INTO MANUAL_DATA_HISTORY (id, data_type, requisition_no, order_no, po_state, line_no, release_no, bag_type, qr_code, part_no, part_desc, quantity, note, delivery_date, created_at)
                     SELECT id, data_type, requisition_no, order_no, po_state, line_no, release_no, bag_type, qr_code, part_no, part_desc, quantity, note, delivery_date, created_at 
                     FROM MANUAL_DATA 
                     WHERE id IN ($placeholders)";

        $mysql->query($sql_move, $matched_ids);

        // Then Delete from Active Table
        $sql_delete = "DELETE FROM MANUAL_DATA WHERE id IN ($placeholders)";
        $mysql->query($sql_delete, $matched_ids);

        echo json_encode([
            'status' => 'success',
            'message' => 'Moved ' . count($matched_ids) . ' manual receipts to history.',
            'cleaned_count' => count($matched_ids),
            'matches' => $matches
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'count' => count($matches),
            'message' => count($matches) . ' matches found in IFS.',
            'matches' => $matches
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>