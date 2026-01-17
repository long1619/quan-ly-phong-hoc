<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../config/connect.php';

// Lấy user_id từ session
$user_id = $_SESSION['user_id'] ?? 0;

// Lấy dữ liệu từ form
$room_id = intval($_POST['room_id'] ?? 0);
$booking_code = 'BK' . date('YmdHis') . rand(100, 999); // Tạo mã đặt phòng tự động
$booking_date = trim($_POST['booking_date'] ?? '');
$start_time = trim($_POST['start_time'] ?? '');
$end_time = trim($_POST['end_time'] ?? '');
$purpose = trim($_POST['purpose'] ?? '');
$participants = intval($_POST['attendees'] ?? 0);
$contact_phone = trim($_POST['phone'] ?? '');
$notes = trim($_POST['notes'] ?? '');

// Validate dữ liệu
$errors = [];

if ($user_id <= 0) {
    $errors[] = 'Phiên đăng nhập không hợp lệ.';
}

if ($room_id <= 0) {
    $errors[] = 'Vui lòng chọn phòng.';
}

if ($booking_date === '') {
    $errors[] = 'Vui lòng chọn ngày sử dụng.';
}

if ($start_time === '') {
    $errors[] = 'Vui lòng chọn giờ bắt đầu.';
}

if ($end_time === '') {
    $errors[] = 'Vui lòng chọn giờ kết thúc.';
}

if ($start_time >= $end_time) {
    $errors[] = 'Giờ kết thúc phải sau giờ bắt đầu.';
}

if ($start_time < '07:00' || $end_time > '21:00') {
    $errors[] = 'Chỉ cho phép đặt phòng từ 07:00 sáng đến 21:00 tối.';
}

if ($purpose === '') {
    $errors[] = 'Vui lòng nhập mục đích sử dụng.';
}

if ($participants <= 0) {
    $errors[] = 'Vui lòng nhập số người tham gia.';
}

if ($contact_phone === '') {
    $errors[] = 'Vui lòng nhập số điện thoại liên hệ.';
}

// Kiểm tra phòng có tồn tại và đang hoạt động không
if (empty($errors)) {
    $stmt = $conn->prepare("SELECT room_name, capacity FROM rooms WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $errors[] = 'Phòng không tồn tại hoặc không hoạt động.';
    } else {
        $room = $result->fetch_assoc();

        // Kiểm tra số người tham gia có vượt quá sức chứa không
        if ($participants > $room['capacity']) {
            $errors[] = 'Số người tham gia (' . $participants . ') vượt quá sức chứa của phòng (' . $room['capacity'] . ').';
        }
    }
    $stmt->close();
}

// Kiểm tra xem phòng đã được đặt trong khoảng thời gian này chưa
if (empty($errors)) {
    $stmt = $conn->prepare("
        SELECT booking_code
        FROM bookings
        WHERE room_id = ?
        AND booking_date = ?
        AND status NOT IN ('da_huy', 'tu_choi')
        AND start_time < ?
        AND end_time > ?
    ");
    $stmt->bind_param("isss", $room_id, $booking_date, $end_time, $start_time);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $errors[] = 'Phòng đã được đặt trong khoảng thời gian này. Vui lòng chọn thời gian khác.';
    }
    $stmt->close();
}

// Nếu có lỗi, quay lại trang đặt phòng
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $_POST;
    header('Location: booking-room.php');
    exit;
}

// Lưu vào database
try {
    // Trạng thái mặc định là 'cho_duyet' (chờ duyệt)
    $status = 'cho_duyet';

    $stmt = $conn->prepare("
        INSERT INTO bookings
        (booking_code, room_id, user_id, booking_date, start_time, end_time, purpose, participants, contact_phone, notes, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param(
        "siissssisss",
        $booking_code,
        $room_id,
        $user_id,
        $booking_date,
        $start_time,
        $end_time,
        $purpose,
        $participants,
        $contact_phone,
        $notes,
        $status
    );

    $booking_inserted = $stmt->execute();

    if ($booking_inserted) {
        $booking_id = $conn->insert_id;
        // Bỏ phần ghi history vì bảng booking_history không tồn tại
        /*
        $action = 'cho_duyet';
        $old_data = null;
        $new_data = json_encode([
            'room_id'    => $room_id,
            'user_id'    => $user_id,
            'date'       => $booking_date,
            'start_time' => $start_time,
            'end_time'   => $end_time,
            'purpose'    => $purpose,
            'attendees'  => $participants,
            'phone'      => $contact_phone,
        ]);
        $reason = null;

        $history_sql = "INSERT INTO booking_history (booking_id, action, action_by, old_data, new_data, reason) VALUES (?, ?, ?, ?, ?, ?)";
        $history_stmt = $conn->prepare($history_sql);
        $history_stmt->bind_param("isisss", $booking_id, $action, $user_id, $old_data, $new_data, $reason);
        $history_stmt->execute();
        $history_stmt->close();
        */

        // Lấy thông tin chi tiết để gửi email
        $sql_booking_info = "
            SELECT
                b.booking_code,
                b.booking_date,
                b.start_time,
                b.end_time,
                b.purpose,
                b.participants,
                b.contact_phone,
                b.notes,
                b.status,
                r.room_code,
                r.room_name,
                u.full_name as user_name,
                u.email as user_email
            FROM bookings b
            INNER JOIN rooms r ON b.room_id = r.id
            INNER JOIN users u ON b.user_id = u.id
            WHERE b.id = ?
        ";

        $stmt_info = $conn->prepare($sql_booking_info);
        $stmt_info->bind_param("i", $booking_id);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();

        if ($result_info->num_rows > 0) {
            $bookingData = $result_info->fetch_assoc();
            $stmt_info->close();

            // Gửi email cho admin
            require_once __DIR__ . '/send-mail-admin.php';
            sendBookingNotificationToAdmin($bookingData);

            // Gửi email xác nhận cho người dùng
            sendBookingNotificationToUser($bookingData);
        }

        $_SESSION['success'] = 'Đặt phòng thành công! Mã đặt phòng: ' . $booking_code . '. Vui lòng chờ phê duyệt.';
        header('Location: ../room/list-room.php');
        exit;
    } else {
        throw new Exception($stmt->error);
    }

} catch (Exception $e) {
    $_SESSION['errors'] = ['Lỗi khi đặt phòng: ' . $e->getMessage()];
    $_SESSION['old'] = $_POST;
    header('Location: booking-room.php');
    exit;
}
?>