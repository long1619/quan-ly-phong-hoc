<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';

// Lấy dữ liệu từ form
$id = intval($_POST['id'] ?? 0);
$type_name = trim($_POST['type_name'] ?? '');
$description = trim($_POST['description'] ?? '');

// Validate
$errors = [];
if ($type_name === '') {
    $errors[] = 'Tên loại phòng không được để trống.';
}

if ($id <= 0) {
    $errors[] = 'ID loại phòng không hợp lệ.';
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $_POST;
    header('Location: edit-type-room.php?id=' . $id);
    exit;
}

// Cập nhật vào DB
try {
    $stmt = $conn->prepare("UPDATE room_types SET type_name = ?, description = ?, WHERE id = ?");
    $stmt->bind_param("ssii", $type_name, $description, $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = 'Cập nhật loại phòng thành công!';
    header('Location: list-type-room.php');
    exit;
} catch (Exception $e) {
    $_SESSION['errors'] = ['Lỗi lưu dữ liệu: ' . $e->getMessage()];
    $_SESSION['old'] = $_POST;
    header('Location: edit-type-room.php?id=' . $id);
    exit;
}