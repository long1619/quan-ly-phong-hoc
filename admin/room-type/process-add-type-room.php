<?php
session_start();
require_once __DIR__ . '/../../config/connect.php'; // Kết nối trả về biến $conn (MySQLi)

// Lấy dữ liệu từ form
$type_name = trim($_POST['type_name'] ?? '');
$description = trim($_POST['description'] ?? '');

// Validate
$errors = [];
if ($type_name === '') {
    $errors[] = 'Tên loại phòng không được để trống.';
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $_POST;
    header('Location: add-type-room.php');
    exit;
}

// Lưu vào DB bằng MySQLi
try {
    // Chuẩn bị câu lệnh
    $stmt = $conn->prepare("INSERT INTO room_types (type_name, description, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $type_name, $description);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = 'Thêm loại phòng thành công!';
    header('Location: list-type-room.php');
    exit;
} catch (Exception $e) {
    $_SESSION['errors'] = ['Lỗi lưu dữ liệu: ' . $e->getMessage()];
    $_SESSION['old'] = $_POST;
    header('Location: add-type-room.php');
    exit;
}