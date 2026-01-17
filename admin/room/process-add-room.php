<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';

// Lấy dữ liệu từ form
$room_code = trim($_POST['room_code'] ?? '');
$room_name = trim($_POST['room_name'] ?? '');
$type_id = intval($_POST['type_id'] ?? 0);
$building = trim($_POST['building'] ?? '');
$floor = intval($_POST['floor'] ?? 0);
$capacity = intval($_POST['capacity'] ?? 0);
$status = trim($_POST['status'] ?? '');
$is_active = 1; // Mặc định luôn là hoạt động khi tạo mới
$facilities = trim($_POST['facilities'] ?? '');

// Xử lý upload ảnh
$image_url = '';
if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] == UPLOAD_ERR_OK) {
    $targetDir = "../../uploads/rooms/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $fileName = time() . '_' . basename($_FILES["image_url"]["name"]);
    $targetFile = $targetDir . $fileName;
    if (move_uploaded_file($_FILES["image_url"]["tmp_name"], $targetFile)) {
        $image_url = "uploads/rooms/" . $fileName;
    }
}

// Validate
$errors = [];
if ($room_code === '') $errors[] = 'Mã phòng không được để trống.';
if ($room_name === '') $errors[] = 'Tên phòng không được để trống.';
if ($type_id <= 0) $errors[] = 'Vui lòng chọn loại phòng.';

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $_POST;
    header('Location: add-room.php');
    exit;
}

// Xử lý facilities: chuyển đổi chuỗi thành JSON
if ($facilities !== '') {
    // Kiểm tra xem đã là JSON hợp lệ chưa
    $test = json_decode($facilities);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Nếu chưa phải JSON, chuyển đổi từ chuỗi phân cách bởi dấu phẩy
        $arr = array_map('trim', explode(',', $facilities));
        // Loại bỏ các phần tử rỗng
        $arr = array_filter($arr, function($item) {
            return $item !== '';
        });
        $facilities = json_encode(array_values($arr), JSON_UNESCAPED_UNICODE);
    }
} else {
    // Nếu rỗng, lưu dưới dạng mảng JSON rỗng
    $facilities = json_encode([]);
}

// Lưu vào DB
try {
    $stmt = $conn->prepare("INSERT INTO rooms (room_code, room_name, type_id, building, floor, capacity, status, is_active, image_url, facilities, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param(
        "ssisississ",
        $room_code,
        $room_name,
        $type_id,
        $building,
        $floor,
        $capacity,
        $status,
        $is_active,
        $image_url,
        $facilities
    );
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = 'Thêm phòng thành công!';
    header('Location: list-room.php');
    exit;
} catch (Exception $e) {
    $_SESSION['errors'] = ['Lỗi lưu dữ liệu: ' . $e->getMessage()];
    $_SESSION['old'] = $_POST;
    header('Location: add-room.php');
    exit;
}