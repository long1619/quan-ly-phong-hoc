<?php

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../../config/connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    try {
        // Kiểm tra xem phòng có tồn tại không
        $checkStmt = $conn->prepare("SELECT room_name FROM rooms WHERE id = ?");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $room = $result->fetch_assoc();
            $checkStmt->close();

            // Xóa phòng
            $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->bind_param('i', $id);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'Xóa phòng "' . htmlspecialchars($room['room_name']) . '" thành công!';
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra khi xóa phòng!';
            }
            $stmt->close();
        } else {
            $checkStmt->close();
            $_SESSION['error'] = 'Phòng không tồn tại!';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
    }
} else {
    $_SESSION['error'] = 'ID phòng không hợp lệ!';
}

header('Location: list-room.php');
exit;
?>