<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile-user.php');
    exit;
}

// Luôn lấy ID từ session để an toàn, không lấy từ POST
$id = intval($_SESSION['user_id']);
$full_name = trim($_POST['full_name'] ?? '');
$password = trim($_POST['password'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$department = trim($_POST['department'] ?? '');

$old = [
    'full_name' => $full_name,
    'email' => $email,
    'phone' => $phone,
    'department' => $department
];

$errors = [];

// Validate full_name
if ($full_name === '') {
    $errors[] = 'Họ và tên không được để trống.';
}

// Validate email
if ($email === '') {
    $errors[] = 'Email không được để trống.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email không đúng định dạng.';
} else {
    // Check trùng email (trừ chính mình)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = 'Email đã được sử dụng bởi người dùng khác.';
    }
    $stmt->close();
}

// Validate phone
if ($phone === '') {
    $errors[] = 'Số điện thoại không được để trống.';
} elseif (!preg_match('/^(0|\+84)[0-9]{9,10}$/', $phone)) {
    $errors[] = 'Số điện thoại không hợp lệ.';
}

// Validate password (nếu nhập)
if ($password !== '' && strlen($password) < 6) {
    $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
}

// Validate avatar
$avatar_path = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Lỗi khi tải ảnh lên.';
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
            $errors[] = 'Chỉ chấp nhận file ảnh (jpg, png, gif, webp).';
        }
        if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Dung lượng ảnh không được vượt quá 2MB.';
        }
    }
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $old;
    header('Location: profile-user.php');
    exit;
}

// Xử lý upload avatar
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../../assets/uploads/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $file_ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $file_name = uniqid('avatar_') . '.' . $file_ext;
    $target_file = $upload_dir . $file_name;
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
        $avatar_path = 'uploads/avatars/' . $file_name;
    }
}

// Cập nhật Database
$fields = [
    'full_name = ?',
    'email = ?',
    'phone = ?',
    'department = ?'
];
$params = [$full_name, $email, $phone, $department];
$types = 'ssss';

if ($avatar_path) {
    $fields[] = 'avatar = ?';
    $params[] = $avatar_path;
    $types .= 's';
}

if ($password !== '') {
    $fields[] = 'password = ?';
    $params[] = sha1($password); // Dùng cùng kiểu hash với hệ thống cũ (sha1)
    $types .= 's';
}

$params[] = $id;
$types .= 'i';

$sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Cập nhật thông tin thành công!';
    // Nếu có đổi avatar hoặc tên, có thể cần update lại session nếu session lưu các thông tin này
    // Tuy nhiên trong navbar thông tin được fetch live từ DB nên không cần lo.
} else {
    $_SESSION['errors'] = ['Có lỗi xảy ra: ' . $conn->error];
}

$stmt->close();
header('Location: profile-user.php');
exit;
?>
