<?php
require_once __DIR__ . '/../config/ifs_config.php';

class IFSConnection {
    private $conn;

    public function connect() {
        if ($this->conn) {
            return $this->conn;
        }

        try {
            $conn_string = getIFSConnectionString();
            // oci_connect(username, password, connection_string, encoding)
            // @ suppresses warnings so we can handle them manually
            $this->conn = @oci_connect(IFS_DB_USER, IFS_DB_PASS, $conn_string, IFS_DB_CHARSET);

            if (!$this->conn) {
                $e = oci_error();
                throw new Exception("IFS Connection Failed: " . $e['message']);
            }

            return $this->conn;

        } catch (Exception $e) {
            // Log error internally, re-throw or return null depending on needs
            error_log($e->getMessage());
            die("Could not connect to IFS Database. Please check configuration.");
        }
    }

    public function query($sql, $params = []) {
        if (!$this->conn) {
            $this->connect();
        }

        $stmt = oci_parse($this->conn, $sql);
        
        if (!$stmt) {
            $e = oci_error($this->conn);
            throw new Exception("Query Parse Error: " . $e['message']);
        }

        foreach ($params as $key => $val) {
            // Oracle named parameters start with :, bind them
            // We assume $val is passed by value. For output params, logic differs.
            oci_bind_by_name($stmt, $key, $params[$key]);
        }

        $r = oci_execute($stmt);
        if (!$r) {
            $e = oci_error($stmt);
            throw new Exception("Query Execution Error: " . $e['message']);
        }

        return $stmt;
    }

    public function fetchAll($stmt) {
        $results = [];
        oci_fetch_all($stmt, $results, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);
        return $results;
    }

    public function close() {
        if ($this->conn) {
            oci_close($this->conn);
            $this->conn = null;
        }
    }
}
?>
