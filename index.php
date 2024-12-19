<?php
require_once './classes/Auth.php';
require_once './classes/User.php';
require_once './classes/Cinema.php';
require_once './classes/Booking.php';
require_once './Middleware/AuthMiddleware.php';


header('Content-Type: application/json');

$auth = new Auth();
$middleware = new AuthMiddleware();

$requestMethod = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';

if ($requestMethod === 'POST' && $endpoint === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? 'user';

    $user = new User();
    if ($user->register($username, $email, $password, $role)) {
        echo json_encode(['status' => 'success', 'message' => 'User registered successfully']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Registration failed']);
    }
}

if ($requestMethod === 'POST' && $endpoint === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $identifier = $data['identifier'] ?? '';
    $password = $data['password'] ?? '';

    $user = new User();
    $result = $user->login($identifier, $password);

    if ($result) {
        echo json_encode(['status' => 'success', 'token' => $result['token'], 'role' => $result['role']]);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
    }
}


$booking = new Booking();
$token = isset($_GET['token']) ? $_GET['token'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'rooms') {
        echo json_encode($booking->getRooms($token));
    }

    if (isset($_GET['action']) && $_GET['action'] === 'movies' && isset($_GET['room_id'])) {
        echo json_encode($booking->getMoviesByRoom($_GET['room_id'], $token));
    }

    if (isset($_GET['action']) && $_GET['action'] === 'seats' && isset($_GET['room_id'])) {
        echo json_encode($booking->getMoviesAndSeatsByRoom($_GET['room_id'], $token));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['action']) && $data['action'] === 'book_seat' && isset($data['seat_id']) && isset($data['movie_id']) && isset($data['token'])) {
        echo $booking->bookSeat($data['token'], $data['seat_id'], $data['movie_id']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters or incorrect action']);
    }
}

?>
