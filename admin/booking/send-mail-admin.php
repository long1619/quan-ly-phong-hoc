<?php
require_once __DIR__ . '/../../config/PHPMailer/Exception.php';
require_once __DIR__ . '/../../config/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../config/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Gửi email thông báo cho Admin
 *
 * @param array $bookingData Thông tin đặt phòng
 * @return bool True nếu gửi thành công, False nếu thất bại
 */
function sendBookingNotificationToAdmin($bookingData) {
    return sendEmail(
        recipientEmail: 'quanphamct003@gmail.com',
        recipientName: 'Admin',
        bookingData: $bookingData,
        messageType: 'booking_notification'
    );
}

/**
 * Gửi email trạng thái "Chờ duyệt" cho người dùng
 *
 * @param array $bookingData Thông tin đặt phòng
 * @return bool True nếu gửi thành công, False nếu thất bại
 */
function sendPendingNotificationToUser($bookingData) {
    return sendEmail(
        recipientEmail: $bookingData['user_email'],
        recipientName: $bookingData['user_name'],
        bookingData: $bookingData,
        messageType: 'pending'
    );
}

/**
 * Gửi email phê duyệt đơn đặt phòng cho người dùng
 *
 * @param array $bookingData Thông tin đặt phòng
 * @return bool True nếu gửi thành công, False nếu thất bại
 */
function sendApprovalNotificationToUser($bookingData) {
    return sendEmail(
        recipientEmail: $bookingData['user_email'],
        recipientName: $bookingData['user_name'],
        bookingData: $bookingData,
        messageType: 'approval'
    );
}

/**
 * Gửi email từ chối đơn đặt phòng cho người dùng
 *
 * @param array $bookingData Thông tin đặt phòng
 * @param string $rejectionReason Lý do từ chối
 * @return bool True nếu gửi thành công, False nếu thất bại
 */
function sendRejectionNotificationToUser($bookingData, $rejectionReason = '') {
    $bookingData['rejection_reason'] = $rejectionReason;
    return sendEmail(
        recipientEmail: $bookingData['user_email'],
        recipientName: $bookingData['user_name'],
        bookingData: $bookingData,
        messageType: 'rejection'
    );
}

/**
 * Gửi email hủy đơn đặt phòng cho người dùng
 *
 * @param array $bookingData Thông tin đặt phòng
 * @param string $cancelReason Lý do hủy
 * @return bool True nếu gửi thành công, False nếu thất bại
 */
function sendCancellationNotificationToUser($bookingData, $cancelReason = '') {
    $bookingData['cancel_reason'] = $cancelReason;
    return sendEmail(
        recipientEmail: $bookingData['user_email'],
        recipientName: $bookingData['user_name'],
        bookingData: $bookingData,
        messageType: 'cancellation'
    );
}

/**
 * Gửi email xác nhận đặt phòng cho người dùng
 *
 * @param array $bookingData Thông tin đặt phòng
 * @return void
 */
function sendBookingNotificationToUser($bookingData) {
    $to = $bookingData['user_email'];
    $subject = 'Xác nhận đặt phòng - ' . $bookingData['booking_code'];

    $message = "
    <html>
        <head>
            <title>Xác nhận đặt phòng</title>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
                .header { background-color: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background-color: white; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
                .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
                .info-row:last-child { border-bottom: none; }
                .label { font-weight: bold; color: #333; }
                .value { color: #666; }
                .status { padding: 10px; background-color: #fff3cd; border-radius: 5px; margin: 15px 0; text-align: center; }
                .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Xác nhận đặt phòng</h2>
                </div>
                <div class='content'>
                    <p>Xin chào <strong>" . htmlspecialchars($bookingData['user_name']) . "</strong>,</p>

                    <p>Cảm ơn bạn đã đặt phòng. Dưới đây là thông tin chi tiết về đơn đặt phòng của bạn:</p>

                    <div class='info-row'>
                        <span class='label'>Mã đặt phòng:</span>
                        <span class='value'>" . htmlspecialchars($bookingData['booking_code']) . "</span>
                    </div>

                    <div class='info-row'>
                        <span class='label'>Phòng:</span>
                        <span class='value'>" . htmlspecialchars($bookingData['room_code']) . " - " . htmlspecialchars($bookingData['room_name']) . "</span>
                    </div>

                    <div class='info-row'>
                        <span class='label'>Ngày sử dụng:</span>
                        <span class='value'>" . date('d/m/Y', strtotime($bookingData['booking_date'])) . "</span>
                    </div>

                    <div class='info-row'>
                        <span class='label'>Thời gian:</span>
                        <span class='value'>" . htmlspecialchars($bookingData['start_time']) . " - " . htmlspecialchars($bookingData['end_time']) . "</span>
                    </div>

                    <div class='info-row'>
                        <span class='label'>Mục đích:</span>
                        <span class='value'>" . htmlspecialchars($bookingData['purpose']) . "</span>
                    </div>

                    <div class='info-row'>
                        <span class='label'>Số người:</span>
                        <span class='value'>" . $bookingData['participants'] . " người</span>
                    </div>

                    <div class='info-row'>
                        <span class='label'>Số điện thoại:</span>
                        <span class='value'>" . htmlspecialchars($bookingData['contact_phone']) . "</span>
                    </div>

                    " . (!empty($bookingData['notes']) ? "
                    <div class='info-row'>
                        <span class='label'>Ghi chú:</span>
                        <span class='value'>" . htmlspecialchars($bookingData['notes']) . "</span>
                    </div>
                    " : "") . "

                    <div class='status'>
                        <strong>Trạng thái: Chờ phê duyệt</strong><br>
                        <small>Quản trị viên sẽ phê duyệt đơn của bạn sớm nhất có thể</small>
                    </div>

                    <p>Nếu có thắc mắc, vui lòng liên hệ với chúng tôi.</p>
                </div>
                <div class='footer'>
                    <p>Đây là email tự động, vui lòng không trả lời email này.</p>
                </div>
            </div>
        </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";

    mail($to, $subject, $message, $headers);
}

/**
 * Hàm chung gửi email
 *
 * @param string $recipientEmail Email người nhận
 * @param string $recipientName Tên người nhận
 * @param array $bookingData Thông tin đặt phòng
 * @param string $messageType Loại tin nhắn (booking_notification, approval, rejection)
 * @return bool True nếu gửi thành công, False nếu thất bại
 */
function sendEmail($recipientEmail, $recipientName, $bookingData, $messageType = 'booking_notification') {
    $mail = new PHPMailer(true);

    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'quanphamct003@gmail.com';
        $mail->Password   = 'wprp cure ywlq hvlt';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Người gửi
        $mail->setFrom('quanphamct003@gmail.com', 'Hệ thống Quản lý Phòng');

        // Người nhận
        $mail->addAddress($recipientEmail, $recipientName);

        // Nội dung email
        $mail->isHTML(true);

        // Xác định tiêu đề và nội dung dựa trên loại tin nhắn
        $subject = getEmailSubject($messageType, $bookingData);
        $body = getEmailTemplate($messageType, $bookingData);

        $mail->Subject = $subject;
        $mail->Body = $body;

        // Gửi email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Lỗi gửi email: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Lấy tiêu đề email dựa trên loại tin nhắn
 *
 * @param string $messageType Loại tin nhắn
 * @param array $bookingData Thông tin đặt phòng
 * @return string Tiêu đề email
 */
function getEmailSubject($messageType, $bookingData) {
    $bookingCode = $bookingData['booking_code'];

    switch ($messageType) {
        case 'pending':
            return "⏳ Đơn đặt phòng đang chờ duyệt - {$bookingCode}";
        case 'approval':
            return "✓ Đơn đặt phòng của bạn đã được phê duyệt - {$bookingCode}";
        case 'rejection':
            return "✕ Đơn đặt phòng của bạn bị từ chối - {$bookingCode}";
        case 'cancellation':
            return "🚫 Đơn đặt phòng đã bị hủy - {$bookingCode}";
        case 'booking_notification':
        default:
            return "Thông báo: Đơn đặt phòng mới - {$bookingCode}";
    }
}

/**
 * Lấy template email và thay thế dữ liệu
 *
 * @param string $messageType Loại tin nhắn
 * @param array $bookingData Thông tin đặt phòng
 * @return string HTML template đã được điền dữ liệu
 */
function getEmailTemplate($messageType, $bookingData) {
    // Format ngày theo định dạng Việt Nam
    $dateObj = new DateTime($bookingData['booking_date']);
    $formattedDate = $dateObj->format('d/m/Y');

    // Xử lý các trường dữ liệu
    $bookingCode = htmlspecialchars($bookingData['booking_code']);
    $userName = htmlspecialchars($bookingData['user_name']);
    $contactPhone = htmlspecialchars($bookingData['contact_phone']);
    $userEmail = htmlspecialchars($bookingData['user_email']);
    $participants = intval($bookingData['participants']);
    $roomCode = htmlspecialchars($bookingData['room_code']);
    $roomName = htmlspecialchars($bookingData['room_name']);
    $purpose = htmlspecialchars($bookingData['purpose']);
    $startTime = htmlspecialchars($bookingData['start_time']);
    $endTime = htmlspecialchars($bookingData['end_time']);
    $notes = !empty($bookingData['notes']) ? htmlspecialchars($bookingData['notes']) : 'Không có ghi chú';
    $rejectionReason = !empty($bookingData['rejection_reason']) ? htmlspecialchars($bookingData['rejection_reason']) : 'Chưa có';
    $cancelReason = !empty($bookingData['cancel_reason']) ? htmlspecialchars($bookingData['cancel_reason']) : 'Không có lý do';

    // Xác định nội dung dựa trên loại tin nhắn
    $headerContent = getHeaderContent($messageType);
    $statusCard = getStatusCard($messageType, $rejectionReason, $cancelReason);
    $actionContent = getActionContent($messageType);

    $template = <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$headerContent['title']}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 50px 20px;">
                <table role="presentation" style="width: 620px; max-width: 100%; border-collapse: collapse; background-color: #ffffff; box-shadow: 0 10px 40px rgba(0,0,0,0.12); border-radius: 16px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background: {$headerContent['bgGradient']}; padding: 45px 35px; text-align: center;">
                            <h1 style="margin: 0 0 12px 0; color: #ffffff; font-size: 32px; font-weight: 700; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">
                                {$headerContent['title']}
                            </h1>
                            <p style="margin: 0; color: rgba(255,255,255,0.95); font-size: 16px; font-weight: 500;">
                                {$headerContent['subtitle']}
                            </p>
                        </td>
                    </tr>

                    <!-- Alert Badge -->
                    <tr>
                        <td style="padding: 25px; text-align: center; background: {$headerContent['badgeBg']};">
                            <span style="display: inline-flex; align-items: center; gap: 10px; background: {$headerContent['badgeGradient']}; color: #ffffff; padding: 12px 28px; border-radius: 30px; font-weight: 700; font-size: 15px; box-shadow: {$headerContent['badgeShadow']}; border: 2px solid rgba(255,255,255,0.3);">
                                <span style="font-size: 18px;">{$headerContent['badgeIcon']}</span>
                                <span>{$headerContent['badgeText']}</span>
                            </span>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 35px;">
                            <!-- Section Header -->
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 3px solid {$headerContent['accentColor']};">
                                <span style="font-size: 24px;">📋</span>
                                <h2 style="margin: 0; color: #1a202c; font-size: 22px; font-weight: 700;">
                                    Thông tin đặt phòng
                                </h2>
                            </div>

                            <!-- Booking Code -->
                            <div style="background: linear-gradient(135deg, #f8f9ff 0%, #e8eaf6 100%); border: 3px dashed #667eea; border-radius: 12px; padding: 25px; margin-bottom: 30px; text-align: center; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15);">
                                <p style="margin: 0 0 8px 0; color: #718096; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px;">
                                    Mã đặt phòng
                                </p>
                                <p style="margin: 0; color: #667eea; font-size: 30px; font-weight: 800; letter-spacing: 3px; text-shadow: 0 2px 4px rgba(102, 126, 234, 0.2);">
                                    {$bookingCode}
                                </p>
                            </div>

                            <!-- Room Info Card -->
                            <div style="background: linear-gradient(135deg, #fff5f7 0%, #fed7e2 100%); border-radius: 12px; padding: 25px; margin-bottom: 25px; border-left: 5px solid #ec4899; box-shadow: 0 4px 12px rgba(236, 72, 153, 0.1);">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                                    <span style="font-size: 22px;">🏠</span>
                                    <strong style="color: #ec4899; font-size: 18px; font-weight: 700;">Thông tin phòng</strong>
                                </div>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 14px 0; color: #9f1239; font-size: 15px; width: 40%; font-weight: 600;">Mã phòng:</td>
                                        <td style="padding: 14px 0; color: #be185d; font-weight: 800; font-size: 18px;">{$roomCode}</td>
                                    </tr>
                                    <tr style="background-color: rgba(255,255,255,0.4);">
                                        <td style="padding: 14px 0; color: #9f1239; font-size: 15px; font-weight: 600;">Tên phòng:</td>
                                        <td style="padding: 14px 0; color: #1a202c; font-weight: 700; font-size: 15px;">{$roomName}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 14px 0; color: #9f1239; font-size: 15px; font-weight: 600;">Mục đích:</td>
                                        <td style="padding: 14px 0; color: #1a202c; font-weight: 700; font-size: 15px;">{$purpose}</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Time Info Card -->
                            <div style="background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%); border-radius: 12px; padding: 25px; margin-bottom: 25px; border-left: 5px solid #14b8a6; box-shadow: 0 4px 12px rgba(20, 184, 166, 0.1);">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                                    <span style="font-size: 22px;">⏰</span>
                                    <strong style="color: #14b8a6; font-size: 18px; font-weight: 700;">Thời gian sử dụng</strong>
                                </div>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 14px 0; color: #115e59; font-size: 15px; width: 40%; font-weight: 600;">Ngày đặt:</td>
                                        <td style="padding: 14px 0; color: #0f766e; font-weight: 800; font-size: 16px;">{$formattedDate}</td>
                                    </tr>
                                    <tr style="background-color: rgba(255,255,255,0.4);">
                                        <td style="padding: 14px 0; color: #115e59; font-size: 15px; font-weight: 600;">Giờ bắt đầu:</td>
                                        <td style="padding: 14px 0; color: #1a202c; font-weight: 700; font-size: 15px;">{$startTime}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 14px 0; color: #115e59; font-size: 15px; font-weight: 600;">Giờ kết thúc:</td>
                                        <td style="padding: 14px 0; color: #1a202c; font-weight: 700; font-size: 15px;">{$endTime}</td>
                                    </tr>
                                </table>
                            </div>

                            {$statusCard}

                            <!-- Action Content -->
                            {$actionContent}
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%); padding: 30px; text-align: center; border-top: 2px solid #e2e8f0;">
                            <p style="margin: 0 0 12px 0; color: #4a5568; font-size: 14px; font-weight: 600;">
                                Email này được gửi tự động từ hệ thống quản lý đặt phòng
                            </p>
                            <p style="margin: 0; color: #a0aec0; font-size: 13px;">
                                © 2025 Hệ thống Quản lý Phòng.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

    return $template;
}

/**
 * Lấy nội dung header dựa trên loại tin nhắn
 */
function getHeaderContent($messageType) {
    $headers = [
        'booking_notification' => [
            'title' => 'Đơn Đặt Phòng Mới',
            'subtitle' => 'Bạn có một đơn đặt phòng mới cần xử lý',
            'bgGradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'badgeBg' => 'linear-gradient(135deg, #fff9e6 0%, #ffecb3 100%)',
            'badgeGradient' => 'linear-gradient(135deg, #ff9800 0%, #f57c00 100%)',
            'badgeShadow' => '0 6px 20px rgba(255, 152, 0, 0.4)',
            'badgeIcon' => '⚡',
            'badgeText' => 'YÊU CẦU XÁC NHẬN',
            'accentColor' => '#667eea'
        ],
        'pending' => [
            'title' => 'Đơn Đặt Phòng Đang Chờ Duyệt',
            'subtitle' => 'Đơn đặt phòng của bạn đang trong trạng thái chờ duyệt',
            'bgGradient' => 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
            'badgeBg' => 'linear-gradient(135deg, #fff9e6 0%, #ffecb3 100%)',
            'badgeGradient' => 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
            'badgeShadow' => '0 6px 20px rgba(245, 158, 11, 0.4)',
            'badgeIcon' => '⏳',
            'badgeText' => 'CHỜ DUYỆT',
            'accentColor' => '#f59e0b'
        ],
        'approval' => [
            'title' => 'Đơn Đặt Phòng Đã Phê Duyệt',
            'subtitle' => 'Đơn đặt phòng của bạn đã được chấp nhận',
            'bgGradient' => 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
            'badgeBg' => 'linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%)',
            'badgeGradient' => 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
            'badgeShadow' => '0 6px 20px rgba(16, 185, 129, 0.4)',
            'badgeIcon' => '✅',
            'badgeText' => 'ĐÃ PHÊ DUYỆT',
            'accentColor' => '#10b981'
        ],
        'rejection' => [
            'title' => 'Đơn Đặt Phòng Bị Từ Chối',
            'subtitle' => 'Đơn đặt phòng của bạn đã bị từ chối',
            'bgGradient' => 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
            'badgeBg' => 'linear-gradient(135deg, #fee2e2 0%, #fecaca 100%)',
            'badgeGradient' => 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
            'badgeShadow' => '0 6px 20px rgba(239, 68, 68, 0.4)',
            'badgeIcon' => '❌',
            'badgeText' => 'BỊ TỪ CHỐI',
            'accentColor' => '#ef4444'
        ],
        'cancellation' => [
            'title' => 'Đơn Đặt Phòng Đã Bị Hủy',
            'subtitle' => 'Đơn đặt phòng của bạn đã bị hủy',
            'bgGradient' => 'linear-gradient(135deg, #6b7280 0%, #4b5563 100%)',
            'badgeBg' => 'linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%)',
            'badgeGradient' => 'linear-gradient(135deg, #6b7280 0%, #4b5563 100%)',
            'badgeShadow' => '0 6px 20px rgba(107, 114, 128, 0.4)',
            'badgeIcon' => '🚫',
            'badgeText' => 'ĐÃ HỦY',
            'accentColor' => '#6b7280'
        ]
    ];

    return $headers[$messageType] ?? $headers['booking_notification'];
}

/**
 * Lấy card hiển thị trạng thái
 */
function getStatusCard($messageType, $rejectionReason, $cancelReason = '') {
    if ($messageType === 'booking_notification') {
        return '';
    }

    if ($messageType === 'pending') {
        return <<<HTML
                            <!-- Pending Message Card -->
                            <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; padding: 25px; margin-bottom: 30px; border-left: 5px solid #f59e0b; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);">
                                <div style="display: flex; align-items: start; gap: 12px;">
                                    <span style="font-size: 28px;">⏳</span>
                                    <div style="flex: 1;">
                                        <p style="margin: 0 0 8px 0; color: #92400e; font-weight: 700; font-size: 18px;">Đơn của bạn đang chờ duyệt</p>
                                        <p style="margin: 0; color: #78350f; line-height: 1.7; font-size: 15px;">
                                            Đơn đặt phòng của bạn đã được ghi nhận và đang trong trạng thái chờ duyệt. Quản trị viên sẽ xử lý đơn của bạn sớm nhất có thể.
                                        </p>
                                    </div>
                                </div>
                            </div>
HTML;
    }

    if ($messageType === 'approval') {
        return <<<HTML
                            <!-- Approval Message Card -->
                            <div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-radius: 12px; padding: 25px; margin-bottom: 30px; border-left: 5px solid #10b981; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);">
                                <div style="display: flex; align-items: start; gap: 12px;">
                                    <span style="font-size: 28px;">✅</span>
                                    <div style="flex: 1;">
                                        <p style="margin: 0 0 8px 0; color: #047857; font-weight: 700; font-size: 18px;">Đơn của bạn đã được phê duyệt!</p>
                                        <p style="margin: 0; color: #065f46; line-height: 1.7; font-size: 15px;">
                                            Phòng của bạn đã sẵn sàng sử dụng vào thời gian đã đặt. Vui lòng kiểm tra lại chi tiết và chuẩn bị sử dụng đúng giờ.
                                        </p>
                                    </div>
                                </div>
                            </div>
HTML;
    }

    if ($messageType === 'rejection') {
        $reasonDisplay = empty($rejectionReason) ? 'Không có lý do được cung cấp' : $rejectionReason;
        return <<<HTML
                            <!-- Rejection Message Card -->
                            <div style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-radius: 12px; padding: 25px; margin-bottom: 30px; border-left: 5px solid #ef4444; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);">
                                <div style="display: flex; align-items: start; gap: 12px;">
                                    <span style="font-size: 28px;">❌</span>
                                    <div style="flex: 1;">
                                        <p style="margin: 0 0 8px 0; color: #7f1d1d; font-weight: 700; font-size: 18px;">Lý do từ chối</p>
                                        <p style="margin: 0; color: #991b1b; line-height: 1.7; font-size: 15px;">
                                            {$reasonDisplay}
                                        </p>
                                    </div>
                                </div>
                            </div>
HTML;
    }

    if ($messageType === 'cancellation') {
        $cancelDisplay = empty($cancelReason) ? 'Không có lý do được cung cấp' : $cancelReason;
        return <<<HTML
                            <!-- Cancellation Message Card -->
                            <div style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-radius: 12px; padding: 25px; margin-bottom: 30px; border-left: 5px solid #6b7280; box-shadow: 0 4px 12px rgba(107, 114, 128, 0.15);">
                                <div style="display: flex; align-items: start; gap: 12px;">
                                    <span style="font-size: 28px;">🚫</span>
                                    <div style="flex: 1;">
                                        <p style="margin: 0 0 8px 0; color: #1f2937; font-weight: 700; font-size: 18px;">Đơn đặt phòng đã bị hủy</p>
                                        <p style="margin: 0 0 12px 0; color: #374151; line-height: 1.7; font-size: 15px;">
                                            <strong>Lý do hủy:</strong> {$cancelDisplay}
                                        </p>
                                        <p style="margin: 0; color: #6b7280; line-height: 1.7; font-size: 14px;">
                                            Nếu bạn cần đặt phòng mới, vui lòng tạo đơn đặt phòng khác.
                                        </p>
                                    </div>
                                </div>
                            </div>
HTML;
    }

    return '';
}

/**
 * Lấy nội dung hành động dựa trên loại tin nhắn
 */
function getActionContent($messageType) {
    if ($messageType === 'booking_notification') {
        return <<<HTML
                            <!-- Action Buttons -->
                            <!-- <div style="text-align: center; margin-top: 35px;">
                                <a href="#" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 16px 42px; border-radius: 30px; font-weight: 700; font-size: 16px; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4); border: 3px solid rgba(255,255,255,0.2); transition: all 0.3s;">
                                    Xem chi tiết & Xử lý
                                </a>
                            </div> -->
HTML;
    }

    if ($messageType === 'pending') {
        return '';
    }

    if ($messageType === 'approval') {
        return <<<HTML
                            <!-- Approval Action -->
                            <!-- <div style="text-align: center; margin-top: 35px;">
                                <a href="#" style="display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; padding: 16px 42px; border-radius: 30px; font-weight: 700; font-size: 16px; box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4); border: 3px solid rgba(255,255,255,0.2); transition: all 0.3s;">
                                    Xem Chi Tiết Đặt Phòng
                                </a>
                            </div> -->
HTML;
    }

    if ($messageType === 'rejection') {
        return <<<HTML
                            <!-- Rejection Action -->
                            <!-- <div style="text-align: center; margin-top: 35px;">
                                <a href="#" style="display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #ffffff; text-decoration: none; padding: 16px 42px; border-radius: 30px; font-weight: 700; font-size: 16px; box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4); border: 3px solid rgba(255,255,255,0.2); transition: all 0.3s;">
                                    Đặt Lại Đơn Khác
                                </a>
                            </div> -->
HTML;
    }

    if ($messageType === 'cancellation') {
        return '';
    }

    return '';
}
?>
