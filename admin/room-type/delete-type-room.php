<?php

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../../config/connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Kiểm tra xem có phòng nào đang sử dụng loại phòng này không
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM rooms WHERE type_id = ?");
    $check_stmt->bind_param('i', $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    $check_stmt->close();

    if ($row['count'] > 0) {
        $_SESSION['error'] = 'Không thể xóa loại phòng này vì đang được sử dụng bởi ' . $row['count'] . ' phòng!';
    } else {
        // Xóa loại phòng
        $stmt = $conn->prepare("DELETE FROM room_types WHERE id = ?");
        $stmt->bind_param('i', $id);

        try {
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Xóa loại phòng thành công!';
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra khi xóa loại phòng!';
            }
        } catch (Exception $e) {
             $_SESSION['error'] = 'Lỗi hệ thống: ' . $e->getMessage();
        }
        $stmt->close();
    }
}
header('Location: list-type-room.php');
exit;
?>