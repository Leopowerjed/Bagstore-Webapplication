<?php
require_once __DIR__ . '/../config/mysql_config.php';

class MySQLConnection
{
    private $conn;

    public function connect()
    {
        if ($this->conn) {
            return $this->conn;
        }

        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($this->conn->connect_error) {
                throw new Exception("MySQL Connection Failed: " . $this->conn->connect_error);
            }

            $this->conn->set_charset(DB_CHARSET);
            return $this->conn;

        } catch (Exception $e) {
            error_log($e->getMessage());
            die("Could not connect to MySQL Database. Please check configuration.");
        }
    }

    public function query($sql, $params = [])
    {
        if (!$this->conn) {
            $this->connect();
        }

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Query Prepare Error: " . $this->conn->error);
        }

        if (!empty($params)) {
            $types = '';
            $values = [];
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_double($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $values[] = $param;
            }
            $stmt->bind_param($types, ...$values);
        }

        $stmt->execute();
        return $stmt;
    }

    public function fetchAll($stmt)
    {
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function close()
    {
        if ($this->conn) {
            $this->conn->close();
            $this->conn = null;
        }
    }
}
?>