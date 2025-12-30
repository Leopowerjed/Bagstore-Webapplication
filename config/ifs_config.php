<?php
// IFS Database Configuration
// Security Note: Ensure this file is not accessible via web browser if possible,
// or use .htaccess to deny access. Exclude from public repositories.

define('IFS_DB_HOST', '128.200.100.4'); // User provided host
define('IFS_DB_PORT', '1521');        // Default Oracle Port
define('IFS_DB_SID', 'PROD');         // User provided service name
define('IFS_DB_USER', 'IFSXTALREPORT'); // User provided username
define('IFS_DB_PASS', 'IFSXTALREPORT'); // User provided password
define('IFS_DB_CHARSET', 'AL32UTF8'); // UTF-8 Character Set

// Connection String Helper
function getIFSConnectionString()
{
    return "//" . IFS_DB_HOST . ":" . IFS_DB_PORT . "/" . IFS_DB_SID;
}
?>