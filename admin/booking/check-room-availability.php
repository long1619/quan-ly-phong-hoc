<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';

$room_id = intval($_GET['room_id'] ?? 0);
$date = $_GET['date'] ?? '';
$start_time = $_GET['start_time'] ?? '';
$end_time = $_GET['end_time'] ?? '';

if ($room_id <= 0 || !$date || !$start_time || !$end_time) {
    echo json_encode(['available' => false, 'message' => 'Thông tin không đầy đủ']);
    exit;
}

$stmt = $conn->prepare("
    SELECT booking_code
    FROM bookings
    WHERE room_id = ?
    AND booking_date = ?
    AND status NOT IN ('da_huy', 'tu_choi')
    AND start_time < ?
    AND end_time > ?
");
$stmt->bind_param("isss", $room_id, $date, $end_time, $start_time);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['available' => false, 'message' => 'Phòng đã được đặt trong khoảng thời gian này.']);
} else {
    echo json_encode(['available' => true]);
}

$stmt->close();
$conn->close();
