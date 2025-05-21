<?php
namespace App\Lib;

use PDO;
use PDOException;

/**
 * This file is used for establishing connections to databases.
 */
class Database{
    private ?PDO $conn = null;

    public function getConnection(): ?PDO {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $this->conn = new PDO($dsn, DB_USER, DB_PASS);

                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } catch(PDOException $exception) {
                error_log("Connection error: " . $exception->getMessage());
                echo "Connection error: " . $exception->getMessage();
                return null;
            }
        }
        return $this->conn;
    }
}
?>