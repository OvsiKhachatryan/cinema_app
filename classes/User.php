<?php
require_once './classes/Database.php';

class User
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function register($username, $email, $password, $role = 'user')
    {
        $checkQuery = 'SELECT COUNT(*) FROM users WHERE username = :username OR email = :email';
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute(['username' => $username, 'email' => $email]);
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Username or email already exists']);
            return false;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $query = 'INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)';
        $stmt = $this->db->prepare($query);

        if ($stmt->execute(['username' => $username, 'email' => $email, 'password' => $hashedPassword, 'role' => $role])) {
            return true;
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Registration failed']);
            return false;
        }
    }

    public function login($identifier, $password)
    {
        $query = 'SELECT * FROM users WHERE username = :identifier OR email = :identifier';
        $stmt = $this->db->prepare($query);
        $stmt->execute(['identifier' => $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $token = bin2hex(random_bytes(16));
            $updateToken = 'UPDATE users SET token = :token WHERE id = :id';
            $updateStmt = $this->db->prepare($updateToken);
            $updateStmt->execute(['token' => $token, 'id' => $user['id']]);
            return ['token' => $token, 'role' => $user['role']];
        }

        return false;
    }

    public function getUserByToken($token)
    {
        $query = 'SELECT * FROM users WHERE token = :token';
        $stmt = $this->db->prepare($query);
        $stmt->execute(['token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

?>
