<?php
require_once './classes/User.php';

class AuthMiddleware
{
    private $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function authenticate($token)
    {
        $user = $this->user->getUserByToken($token);
        if ($user) {
            return $user;
        }
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    public function authorizeRole($token, $role)
    {
        $user = $this->authenticate($token);
        if ($user['role'] !== $role) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
            exit;
        }
        return $user;
    }
}

?>
