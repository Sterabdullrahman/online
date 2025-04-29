<?php
require_once 'db.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username AND is_active = 1");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['hospital_id'] = $user['hospital_id'] ?? null;
            
            // Update last login
            $this->updateLastLogin($user['user_id']);
            
            return true;
        }
        
        return false;
    }

    private function updateLastLogin($user_id) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }

    public function registerPatient($data) {
        // Validate and sanitize data first
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO users 
            (username, email, password, phone, full_name, user_type, created_at) 
            VALUES 
            (:username, :email, :password, :phone, :full_name, 'patient', NOW())
        ");
        
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':full_name', $data['full_name']);
        
        return $stmt->execute();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function redirectIfNotLoggedIn() {
        if (!$this->isLoggedIn()) {
            header("Location: auth/login.php");
            exit();
        }
    }

    public function checkUserType($allowed_types) {
        if (!in_array($_SESSION['user_type'], $allowed_types)) {
            header("Location: index.php");
            exit();
        }
    }
}
?>