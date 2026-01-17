<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

if (isset($_POST['booking_code']) && isset($_POST['rejection_reason'])) {
    $booking_code = trim($_POST['booking_code']);
    $rejection_reason = trim($_POST['rejection_reason']);

    if ($rejection_reason === '') {
        $_SESSION['errors'] = ['Vui lòng nhập lý do từ chối!'];
        header('Location: approve-room.php');
        exit;
    }

    // Lấy thông tin đơn đặt phòng trước khi cập nhật
    $sql = "
        SELECT
            b.*,
            u.email as user_email,
            u.full_name as user_name,
            r.room_code,
            r.room_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
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

    // Cập nhật trạng thái và lý do từ chối
    $rejected_by = $_SESSION['user_id'];
    $update_stmt = $conn->prepare("UPDATE bookings SET status = 'tu_choi', rejection_reason = ?, rejected_by = ?, rejected_at = NOW(), updated_at = NOW() WHERE booking_code = ? AND status = 'cho_duyet'");
    $update_stmt->bind_param("sis", $rejection_reason, $rejected_by, $booking_code);

    if ($update_stmt->execute()) {
        $update_stmt->close();

        // Gửi email từ chối cho người dùng
        require_once __DIR__ . '/../booking/send-mail-admin.php';

        $emailSent = sendRejectionNotificationToUser($booking, $rejection_reason);

        if ($emailSent) {
            error_log("Rejection email sent successfully for booking: " . $booking_code);
            $_SESSION['success'] = 'Đơn đặt phòng ' . $booking_code . ' đã bị từ chối! Email thông báo đã được gửi.';
        } else {
            error_log("Failed to send rejection email for booking: " . $booking_code);
            $_SESSION['success'] = 'Đơn đặt phòng ' . $booking_code . ' đã bị từ chối! (Email gửi không thành công)';
        }
    } else {
        $_SESSION['errors'] = ['Lỗi khi cập nhật trạng thái đơn.'];
    }
} else {
    $_SESSION['errors'] = ['Dữ liệu không hợp lệ.'];
}

header('Location: approve-room.php');
exit;
?>