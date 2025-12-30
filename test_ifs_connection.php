<?php
// Test Script for IFS Connection
// Usage: php test_ifs_connection.php

require_once 'includes/IFSConnection.php';

echo "Testing connection to IFS Database at " . IFS_DB_HOST . "...\n";

try {
    $db = new IFSConnection();
    $conn = $db->connect();

    if ($conn) {
        echo "SUCCESS: Connected to IFS Database!\n\n";

        // Try a simple query
        // 'DUAL' is a dummy table in Oracle
        echo "Executing test query (SELECT SYSDATE FROM DUAL)...\n";
        $stmt = $db->query("SELECT SYSDATE as CURRENT_TIME FROM DUAL");

        $row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS);

        if ($row) {
            echo "Server Time: " . $row['CURRENT_TIME'] . "\n";
        } else {
            echo "Query executed but returned no rows?\n";
        }

        $db->close();
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Please check:\n";
    echo "1. Oracle Instant Client is installed and in PATH.\n";
    echo "2. config/ifs_config.php details are correct.\n";
    echo "3. VPN/Network connection to IFS server is active.\n";
}
?>