<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

if (isset($_POST['booking_code']) && isset($_POST['cancel_reason'])) {
    $booking_code = trim($_POST['booking_code']);
    $cancel_reason = trim($_POST['cancel_reason']);
    $canceled_by = $_SESSION['user_id'];

    if ($cancel_reason === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập lý do hủy!'
        ]);
        exit;
    }

    // Kiểm tra đơn có tồn tại và trạng thái cho phép hủy
    $sql = "SELECT * FROM bookings WHERE booking_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $booking_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy đơn đặt phòng.'
        ]);
        exit;
    }

    $booking = $result->fetch_assoc();
    $stmt->close();

    // Chỉ cho phép hủy khi trạng thái là 'cho_duyet' hoặc 'da_duyet'
    if ($booking['status'] !== 'cho_duyet' && $booking['status'] !== 'da_duyet') {
        echo json_encode([
            'success' => false,
            'message' => 'Chỉ có thể hủy đơn khi trạng thái là Chờ duyệt hoặc Đã duyệt.'
        ]);
        exit;
    }

    // Validate: Nếu còn dưới 1 tiếng trước giờ bắt đầu thì không cho hủy
    $now = new DateTime();
    $bookingDate = $booking['booking_date'];
    $startTime = $booking['start_time'];
    // Chuẩn hóa start_time về định dạng H:i nếu có giây
    $startTime = strlen($startTime) > 5 ? substr($startTime, 0, 5) : $startTime;
    $startDateTime = DateTime::createFromFormat('Y-m-d H:i', $bookingDate . ' ' . $startTime);
    if ($startDateTime) {
        $diff = $startDateTime->getTimestamp() - $now->getTimestamp();
        if ($diff < 3600) {
            echo json_encode([
                'success' => false,
                'message' => 'Thời gian cho phép hủy phòng là 1 tiếng sau khi đặt.'
            ]);
            exit;
        }
    }

    // Cập nhật trạng thái sang 'da_huy', lý do, người hủy, thời gian hủy
    $update_stmt = $conn->prepare("UPDATE bookings SET status = 'da_huy', cancel_reason = ?, canceled_by = ?, canceled_at = NOW(), updated_at = NOW() WHERE booking_code = ?");
    $update_stmt->bind_param("sis", $cancel_reason, $canceled_by, $booking_code);

    if ($update_stmt->execute()) {
        $update_stmt->close();
        echo json_encode([
            'success' => true,
            'message' => 'Đơn đặt phòng đã được hủy thành công!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi cập nhật trạng thái đơn.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ.'
    ]);
}
exit;
?>