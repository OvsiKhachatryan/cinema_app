<?php
require_once 'User.php';

class Auth
{
    private $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function login($username, $password)
    {
        $result = $this->user->login($username, $password);

        if ($result) {

            return json_encode([
                'status' => 'success',
                'token' => $result['token'],
                'role' => $result['role'],
                'message' => 'Login successful'
            ]);
        }

        return json_encode([
            'status' => 'error',
            'message' => 'Invalid credentials'
        ]);
    }

    public function checkAuth($token)
    {
        $user = $this->user->getUserByToken($token);
        if ($user) {
            return json_encode([
                'status' => 'success',
                'user' => $user
            ]);
        }

        return json_encode([
            'status' => 'error',
            'message' => 'Unauthorized, please log in'
        ]);
    }

    public function isUser($token)
    {
        $user = $this->user->getUserByToken($token);

        if ($user && $user['role'] === 'user') {
            return true;
        }

        return false;
    }

    public function isAdmin($token)
    {
        $user = $this->user->getUserByToken($token);

        if ($user && $user['role'] === 'admin') {
            return true;
        }

        return false;
    }
}

?>
