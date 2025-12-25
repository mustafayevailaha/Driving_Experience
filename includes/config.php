<?php
// includes/config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Database {
    private $host = "mysql-ilaha2.alwaysdata.net";
    private $db_name = "ilaha2_driving_experience";
    private $username = "ilaha2_user";
    private $password = "Project_2025";
    public $conn;
    
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch(PDOException $exception) {
            // You can log errors to a file instead of displaying them
            error_log("Database connection error: " . $exception->getMessage());
            return null;
        }
    }
}

session_start();
?>
