<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

$booking_code = trim($_POST['booking_code'] ?? '');
$action = trim($_POST['action'] ?? '');
$rejection_reason = trim($_POST['rejection_reason'] ?? '');

if (empty($booking_code)) {
    $_SESSION['errors'] = ['Mã đặt phòng không hợp lệ.'];
    header('Location: approve-room.php');
    exit;
}

// Lấy thông tin đơn đặt phòng
$sql = "
    SELECT
        b.*,
        u.email as user_email,
        u.full_name as user_name,
        r.room_code,
        r.room_name,
        rt.type_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    JOIN room_types rt ON r.type_id = rt.id
    WHERE b.booking_code = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $booking_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['errors'] = ['Không tìm thấy đơn đặt phòng.'];
    header('Location: approve-room.php');
    exit;
}

$booking = $result->fetch_assoc();
$stmt->close();

// Xử lý phê duyệt
if ($action === 'approve') {
    $new_status = 'da_duyet';
    $approved_by = $_SESSION['user_id'];
    $update_sql = "UPDATE bookings SET status = ?, approved_by = ?, approved_at = NOW(), updated_at = NOW() WHERE booking_code = ?";

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sis", $new_status, $approved_by, $booking_code);

    if ($stmt->execute()) {
        $stmt->close();

        // Gửi email phê duyệt cho người dùng
        require_once __DIR__ . '/../booking/send-mail-admin.php';

        $emailSent = sendApprovalNotificationToUser($booking);

        if ($emailSent) {
            error_log("Approval email sent successfully for booking: " . $booking_code);
        } else {
            error_log("Failed to send approval email for booking: " . $booking_code);
        }

        $_SESSION['success'] = 'Đơn đặt phòng ' . $booking_code . ' đã được phê duyệt! Email xác nhận đã được gửi.';
    } else {
        $_SESSION['errors'] = ['Lỗi khi cập nhật trạng thái đơn: ' . $stmt->error];
    }
}

// Xử lý từ chối
elseif ($action === 'reject') {
    if (empty($rejection_reason)) {
        $_SESSION['errors'] = ['Vui lòng nhập lý do từ chối.'];
        header('Location: approve-room.php');
        exit;
    }

    $new_status = 'tu_choi';
    $rejected_by = $_SESSION['user_id'];
    $update_sql = "UPDATE bookings SET status = ?, rejection_reason = ?, rejected_by = ?, rejected_at = NOW(), updated_at = NOW() WHERE booking_code = ?";

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssis", $new_status, $rejection_reason, $rejected_by, $booking_code);

    if ($stmt->execute()) {
        $stmt->close();

        // Gửi email từ chối cho người dùng
        require_once __DIR__ . '/../booking/send-mail-admin.php';

        $emailSent = sendRejectionNotificationToUser($booking, $rejection_reason);

        if ($emailSent) {
            error_log("Rejection email sent successfully for booking: " . $booking_code);
        } else {
            error_log("Failed to send rejection email for booking: " . $booking_code);
        }

        $_SESSION['success'] = 'Đơn đặt phòng ' . $booking_code . ' đã bị từ chối! Email thông báo đã được gửi.';
    } else {
        $_SESSION['errors'] = ['Lỗi khi cập nhật trạng thái đơn: ' . $stmt->error];
    }
}

else {
    $_SESSION['errors'] = ['Hành động không hợp lệ.'];
}

header('Location: approve-room.php');
exit;
?>