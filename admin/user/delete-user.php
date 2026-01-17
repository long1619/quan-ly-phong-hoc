<?php
    session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../auth/login.php');
        exit;
    }
    require_once __DIR__ . '/../../config/connect.php';

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id > 0) {
        // Xóa user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Xóa người dùng thành công!';
        } else {
            $_SESSION['success'] = 'Có lỗi xảy ra khi xóa người dùng!';
        }
    }
    header('Location: list-user.php');
    exit;
?>