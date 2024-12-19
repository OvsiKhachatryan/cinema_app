<?php
require_once 'User.php';
require_once 'Cinema.php';

class Booking
{
    private $user;
    private $cinema;

    public function __construct()
    {
        $this->user = new User();
        $this->cinema = new Cinema();
    }

    public function isAuthenticated($token)
    {
        $user = $this->user->getUserByToken($token);
        return $user && $user['role'] === 'user' ? $user : false;
    }

    public function getRooms($token)
    {
        return $this->cinema->getRooms($token);
    }

    public function getMoviesByRoom($roomId, $token)
    {
        return $this->cinema->getMoviesByRoom($roomId, $token);
    }

    public function getMoviesAndSeatsByRoom($movieId, $token)
    {
        return $this->cinema->getMoviesAndSeatsByRoom($movieId, $token);
    }

    public function bookSeat($token, $seatId, $movieId)
    {
        $user = $this->isAuthenticated($token);
        if (!$user) {
            return json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $result = $this->cinema->bookSeat($token, $seatId, $movieId);

        return json_encode($result);
    }
}

?>
