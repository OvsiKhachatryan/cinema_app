<?php
require_once 'Database.php';
require_once './Middleware/AuthMiddleware.php';

class Cinema
{
    private $db;
    private $authMiddleware;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
        $this->authMiddleware = new AuthMiddleware();
    }

    public function getRooms($token)
    {
        $this->authMiddleware->authenticate($token);

        $query = 'SELECT * FROM rooms';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMoviesByRoom($roomId, $token)
    {
        $this->authMiddleware->authenticate($token);

        $currentDateTime = date('Y-m-d H:i:s');

        $query = 'SELECT * FROM movies WHERE room_id = :room_id AND start_time >= :current_date_time';
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindParam(':current_date_time', $currentDateTime, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMoviesAndSeatsByRoom($roomId, $token)
    {
        $this->authMiddleware->authenticate($token);

        $currentDateTime = date('Y-m-d H:i:s');

        $query = 'SELECT m.id AS movie_id, m.name AS movie_name, m.start_time, r.name AS room_name
          FROM movies m
          INNER JOIN rooms r ON m.room_id = r.id
          WHERE m.room_id = :room_id AND m.start_time >= :current_date_time';

        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindParam(':current_date_time', $currentDateTime, PDO::PARAM_STR);

        $stmt->execute();

        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $moviesWithSeats = [];

        foreach ($movies as $movie) {
            $movieId = $movie['movie_id'];

            $seatQuery = ' SELECT s.id AS seat_id, s.row, s.seat_number, IF(b.seat_id IS NULL, 0, 1) AS is_booked
            FROM seats s
            LEFT JOIN bookings b ON s.id = b.seat_id AND b.movie_id = :movie_id
            WHERE s.room_id = :room_id
        ';
            $seatStmt = $this->db->prepare($seatQuery);
            $seatStmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            $seatStmt->bindParam(':room_id', $roomId, PDO::PARAM_INT);
            $seatStmt->execute();

            $seats = $seatStmt->fetchAll(PDO::FETCH_ASSOC);

            $movie['seats'] = $seats;
            $moviesWithSeats[] = $movie;
        }

        return $moviesWithSeats;
    }

    public function bookSeat($token, $seatId, $movieId)
    {
        $this->authMiddleware->authenticate($token);

        $query = 'SELECT * FROM seats WHERE id = :seat_id';
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':seat_id', $seatId, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return ['status' => 'error', 'message' => 'Failed to execute seat query'];
        }
        $seat = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$seat) {
            return ['status' => 'error', 'message' => 'Seat not found'];
        }

        if ($seat['is_booked'] == 1) {
            return ['status' => 'error', 'message' => 'Seat already booked'];
        }

        $user = (new User())->getUserByToken($token);
        if (!$user) {
            return ['status' => 'error', 'message' => 'User not found'];
        }

        date_default_timezone_set('Asia/Yerevan');
        $currentTimestamp = date('Y-m-d H:i:s');

        try {
            $this->db->beginTransaction();

            $insertBookingQuery = 'INSERT INTO bookings (user_id, seat_id, movie_id, booked_at) VALUES (:user_id, :seat_id, :movie_id, :booked_at)';
            $insertStmt = $this->db->prepare($insertBookingQuery);
            $insertStmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
            $insertStmt->bindParam(':seat_id', $seatId, PDO::PARAM_INT);
            $insertStmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            $insertStmt->bindParam(':booked_at', $currentTimestamp, PDO::PARAM_STR);

            if (!$insertStmt->execute()) {
                $errorInfo = $insertStmt->errorInfo();
                throw new Exception('Failed to book seat: ' . implode(", ", $errorInfo));
            }

            $updateSeatQuery = 'UPDATE seats SET is_booked = 1 WHERE id = :seat_id';
            $updateSeatStmt = $this->db->prepare($updateSeatQuery);
            $updateSeatStmt->bindParam(':seat_id', $seatId, PDO::PARAM_INT);

            if (!$updateSeatStmt->execute()) {
                $errorInfo = $updateSeatStmt->errorInfo();
                throw new Exception('Failed to update seat status: ' . implode(", ", $errorInfo));
            }

            $this->db->commit();

            return ['status' => 'success', 'message' => 'Seat booked successfully'];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
