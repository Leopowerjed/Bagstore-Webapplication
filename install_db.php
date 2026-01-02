<?php
require_once __DIR__ . '/config/mysql_config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    if ($conn->query($sql) === TRUE) {
        echo "Database created successfully\n";
    } else {
        echo "Error creating database: " . $conn->error . "\n";
    }

    $conn->select_db(DB_NAME);

    // Run schema.sql
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');

    // Split schema into separate queries (basic split by ;)
    $queries = explode(';', $schema);
    foreach ($queries as $query) {
        $q = trim($query);
        if (!empty($q)) {
            if ($conn->query($q) === TRUE) {
                // Success
            } else {
                echo "Error executing query: " . $conn->error . "\n";
            }
        }
    }

    echo "Schema initialized successfully\n";

    // Run additional migration (the one I just created)
    $sql_alter = "
        ALTER TABLE MANUAL_DATA 
        ADD COLUMN data_type ENUM('RECEIPT', 'ISSUE') DEFAULT 'RECEIPT' AFTER id,
        ADD COLUMN requisition_no VARCHAR(50) AFTER data_type,
        ADD COLUMN line_no VARCHAR(10) AFTER requisition_no,
        ADD COLUMN release_no VARCHAR(10) AFTER line_no;
    ";

    if ($conn->query($sql_alter) === TRUE) {
        echo "Migration applied successfully\n";
    } else {
        echo "Migration error: " . $conn->error . "\n";
    }

    $conn->close();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>