<?php
    session_start();

    // Kiểm tra request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: login.php');
        exit;
    }

    // Validate input
    $email_username = isset($_POST['email-username']) ? trim($_POST['email-username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    $errors = [];
    $errors_field = [];

    if (empty($email_username)) {
        $errors_field['email'] = 'Email hoặc tên đăng nhập không được để trống';
        $errors[] = 'Vui lòng nhập email hoặc tên đăng nhập';
    }

    if (empty($password)) {
        $errors_field['password'] = 'Mật khẩu không được để trống';
        $errors[] = 'Vui lòng nhập mật khẩu';
    }

    // Nếu có lỗi validate, quay lại login
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['errors_field'] = $errors_field;
        $_SESSION['email_username'] = $email_username;
        header('Location: login.php');
        exit;
    }
    try {
        // Sửa lại đường dẫn include cho đúng
        include __DIR__ . '/../../config/connect.php';

        $query = "SELECT id, username, password, role FROM users WHERE email = ? OR username = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            echo "Lỗi prepare: " . $conn->error;
            die();
        }
        $stmt->bind_param('ss', $email_username, $email_username);
        if (!$stmt->execute()) {
            echo "Lỗi execute: " . $stmt->error;
            die();
        }
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        echo "username: " . $user['username'] . "<br>";
        echo "password: " . $user['password'] . "<br>";


        if ($user['password'] != sha1($password)) {
        // if ($user['password'] != $password) {
            $_SESSION['errors'] = ['Email/Username hoặc mật khẩu không chính xác'];
            $_SESSION['email_username'] = $email_username;
            header('Location: login.php');
            exit;
        }

        // Login thành công
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        unset($_SESSION['errors']);
        unset($_SESSION['errors_field']);
        unset($_SESSION['email_username']);

        header('Location: ../dashboard/index.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['errors'] = [
            'Lỗi hệ thống: ' . $e->getMessage(),
            'Chi tiết: Liên hệ quản trị viên nếu lỗi tiếp tục'
        ];
        header('Location: login.php');
        exit;
    }
    ?>
