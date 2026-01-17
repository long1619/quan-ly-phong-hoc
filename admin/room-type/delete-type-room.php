<?php

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../../config/connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Xóa loại phòng
    $stmt = $conn->prepare("DELETE FROM room_types WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Xóa loại phòng thành công!';
    } else {
        $_SESSION['success'] = 'Có lỗi xảy ra khi xóa loại phòng!';
    }
    $stmt->close();
}
header('Location: list-type-room.php');
exit;
?>