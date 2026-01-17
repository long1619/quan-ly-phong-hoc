<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';
require_once __DIR__ . '/../../helpers/helpers.php';

$userRole = $_SESSION['user_role'] ?? '';

// Kiểm tra quyền xóa tin tức
if (!checkPermission($conn, $userRole, 'delete_news')) {
    echo "<script>alert('Bạn không có quyền xóa tin tức!'); window.location.href='../dashboard/index.php';</script>";
    exit;
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Xóa tin tức thành công!';
    } else {
        $_SESSION['errors'] = ['Lỗi khi xóa tin tức: ' . $conn->error];
    }
    $stmt->close();
} else {
    $_SESSION['errors'] = ['Thiếu ID tin tức!'];
}

header('Location: list-news.php');
exit;