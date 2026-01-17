<?php
// filepath: c:\xampp 8.2\htdocs\quan-ly-phong-hoc\admin\user\process-edit-user.php
session_start();
require_once __DIR__ . '/../../config/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list-user.php');
    exit;
}

$id = intval($_POST['id']);
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$role = trim($_POST['role'] ?? '');
$student_code = trim($_POST['student_code'] ?? '');
$employee_code = trim($_POST['employee_code'] ?? '');
$department = trim($_POST['department'] ?? '');

$old = [
    'username' => $username,
    'full_name' => $full_name,
    'email' => $email,
    'phone' => $phone,
    'role' => $role,
    'student_code' => $student_code,
    'employee_code' => $employee_code,
    'department' => $department
];

$errors = [];

// Validate username
if ($username === '') {
    $errors[] = 'Tên đăng nhập không được để trống.';
} elseif (!preg_match('/^[a-zA-Z0-9_]{4,}$/', $username)) {
    $errors[] = 'Tên đăng nhập phải từ 4 ký tự, chỉ gồm chữ, số, dấu gạch dưới.';
} else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = 'Tên đăng nhập đã tồn tại.';
    }
    $stmt->close();
}

// Validate password (nếu nhập)
if ($password !== '' && strlen($password) < 6) {
    $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
}

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
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = 'Email đã tồn tại.';
    }
    $stmt->close();
}

// Validate phone
if ($phone === '') {
    $errors[] = 'Số điện thoại không được để trống.';
} elseif (!preg_match('/^(0|\+84)[0-9]{9,10}$/', $phone)) {
    $errors[] = 'Số điện thoại không đúng định dạng.';
}

// Validate role
if ($role === '') {
    $errors[] = 'Vui lòng chọn vai trò.';
} elseif (!in_array($role, ['sinh_vien', 'giang_vien', 'admin'])) {
    $errors[] = 'Vai trò không hợp lệ.';
}

// Validate student_code/employee_code
if ($role === 'sinh_vien' && $student_code === '') {
    $errors[] = 'Mã sinh viên không được để trống.';
}
if ($role === 'giang_vien' && $employee_code === '') {
    $errors[] = 'Mã giảng viên không được để trống.';
}

// Validate department
if ($department === '') {
    $errors[] = 'Phòng ban không được để trống.';
}

// Validate avatar
$avatar_path = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Lỗi khi upload ảnh đại diện.';
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
            $errors[] = 'Ảnh đại diện phải là file ảnh (jpg, png, gif, webp).';
        }
        if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ảnh đại diện không được vượt quá 2MB.';
        }
    }
}

// Nếu có lỗi, lưu vào session và chuyển hướng về lại form edit
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $old;
    header('Location: edit-user.php?id=' . $id);
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

// Xây dựng câu lệnh SQL động
$fields = [
    'username = ?',
    'full_name = ?',
    'email = ?',
    'phone = ?',
    'role = ?',
    'student_code = ?',
    'employee_code = ?',
    'department = ?'
];
$params = [
    $username,
    $full_name,
    $email,
    $phone,
    $role,
    $student_code,
    $employee_code,
    $department
];
$types = 'ssssssss';

if ($avatar_path) {
    $fields[] = 'avatar = ?';
    $params[] = $avatar_path;
    $types .= 's';
}

if ($password !== '') {
    $fields[] = 'password = ?';
    $params[] = sha1($password);
    $types .= 's';
}

$params[] = $id;
$types .= 'i';

$sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Cập nhật người dùng thành công!';
    $stmt->close();
    if ($role === 'admin') {
        header('Location: edit-user.php?id=' . $id);
    } else {
        header('Location: list-user.php');
    }
    exit;
} else {
    $_SESSION['errors'] = ['Lỗi khi cập nhật người dùng: ' . $stmt->error];
    $_SESSION['old'] = $old;
    header('Location: edit-user.php?id=' . $id);
    exit;
}
?>