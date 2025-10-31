<?php
/**
 * Lớp kết nối cơ sở dữ liệu
 * Sử dụng PDO để kết nối MySQL
 */
class Database {
    private $host = 'localhost';
    private $database_name = 'quanlyxekhach';
    private $username = 'root';
    private $password = '';
    public $conn;

    /**
     * Tạo kết nối đến cơ sở dữ liệu
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->database_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            echo "Lỗi kết nối cơ sở dữ liệu: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

