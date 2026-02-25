<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

include __DIR__ . '/../common/header.php';
require_once __DIR__ . '/../../helpers/helpers.php';
$userRole = $_SESSION['user_role'] ?? '';

// Kiểm tra quyền xem đơn đã hủy
if (!checkPermission($conn, $userRole, 'view_canceled')) {
    echo "<script>alert('Bạn không có quyền xem thông tin này!'); window.location.href='../dashboard/index.php';</script>";
    exit;
}

require_once __DIR__ . '/../common/alert.php';

// Lấy danh sách các đơn đã hủy
$query = "
    SELECT
        b.*,
        r.room_code,
        r.room_name,
        rt.type_name,
        r.capacity,
        u.full_name AS canceled_by_name
    FROM bookings b
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN room_types rt ON r.type_id = rt.id
    LEFT JOIN users u ON b.canceled_by = u.id
    WHERE b.status = 'da_huy'
    ORDER BY b.canceled_at DESC
";
$result = $conn->query($query);
$canceledBookings = [];
while ($row = $result->fetch_assoc()) {
    $canceledBookings[] = $row;
}
?>
<style>
.bookings-list { display: grid; gap: 16px; }
.booking-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 24px; border-left: 4px solid #6b7280; }
.booking-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.booking-code { font-size: 20px; font-weight: 700; color: #1a202c; }
.status-badge { padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; background: #f3f4f6; color: #6b7280; }
.booking-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
.info-item { display: flex; flex-direction: column; gap: 4px; }
.info-label { font-size: 12px; color: #718096; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
.info-value { font-size: 14px; color: #1a202c; font-weight: 600; }
.rejection-reason { background: #fee2e2; border-left: 4px solid #ef4444; padding: 12px 16px; border-radius: 8px; margin-top: 16px; }
.rejection-label { font-size: 12px; font-weight: 600; color: #dc2626; margin-bottom: 4px; }
.rejection-text { font-size: 14px; color: #991b1b; }
.empty-state { text-align: center; padding: 60px 20px; color: #999; }
.empty-state-icon { font-size: 64px; margin-bottom: 16px; }
.empty-state-text { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
.btn-detail {
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 10px;
}
.btn-detail:hover {
    background: #2563eb;
}
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.6);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.modal-overlay.active { display: flex; }
.modal-content {
    background: white;
    border-radius: 16px;
    max-width: 700px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: slideUp 0.3s;
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px);}
    to { opacity: 1; transform: translateY(0);}
}
.modal-header {
    padding: 24px;
    border-bottom: 2px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px 16px 0 0;
}
.modal-title { font-size: 22px; font-weight: 700; }
.modal-close {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 36px; height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 24px;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
}
.modal-close:hover { background: rgba(255,255,255,0.3); transform: rotate(90deg);}
.modal-body { padding: 32px; }
.detail-section { margin-bottom: 32px; }
.section-title { font-size: 18px; font-weight: 700; color: #1a202c; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;}
.section-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; color: white;}
.section-icon.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);}
.section-icon.success { background: linear-gradient(135deg, #10b981 0%, #059669 100%);}
.detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; background: #f9fafb; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb;}
.detail-item { display: flex; flex-direction: column; gap: 6px;}
.detail-label { font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;}
.detail-value { font-size: 15px; color: #1f2937; font-weight: 600;}
.modal-footer { padding: 20px 32px; border-top: 2px solid #e2e8f0; display: flex; gap: 12px; background: #f9fafb; border-radius: 0 0 16px 16px;}
.btn-modal { flex: 1; padding: 14px 28px; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s;}
.btn-modal.secondary { background: white; color: #4b5563; border: 2px solid #e5e7eb;}
.btn-modal.secondary:hover { background: #f3f4f6;}
@media (max-width: 600px) {
    .booking-header { flex-direction: column; }
    .modal-footer { flex-direction: column; }
}
</style>
<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include __DIR__ . '/../common/menu-sidebar.php'; ?>
            <div class="layout-page">
                <?php include __DIR__ . '/../common/navbar.php'; ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="mb-1">🚫 Danh sách các đơn đã hủy</h4>
                                <p class="text-muted mb-0">Tất cả các đơn đặt phòng đã bị hủy</p>
                            </div>
                        </div>
                        <div class="bookings-list">
                            <?php if (empty($canceledBookings)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">📦</div>
                                    <div class="empty-state-text">Không có đơn đã hủy nào</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($canceledBookings as $booking): ?>
                                    <?php
                                        $bookingDate = date('d/m/Y', strtotime($booking['booking_date']));
                                        $timeRange = substr($booking['start_time'], 0, 5) . ' - ' . substr($booking['end_time'], 0, 5);
                                        $canceledAt = $booking['canceled_at'] ? date('d/m/Y H:i', strtotime($booking['canceled_at'])) : '--';
                                    ?>
                                    <div class="booking-card">
                                        <div class="booking-header">
                                            <div>
                                                <div class="booking-code"><?php echo htmlspecialchars($booking['booking_code']); ?></div>
                                                <p class="text-muted mb-0" style="font-size: 12px;">Ngày đặt: <?php echo $bookingDate; ?></p>
                                            </div>
                                            <span class="status-badge">🚫 Đã hủy</span>
                                        </div>
                                        <div class="booking-grid">
                                            <div class="info-item">
                                                <div class="info-label">Phòng</div>
                                                <div class="info-value"><?php echo htmlspecialchars($booking['room_code']); ?> - <?php echo htmlspecialchars($booking['room_name']); ?></div>
                                            </div>
                                            <div class="info-item">
                                                <div class="info-label">Thời gian</div>
                                                <div class="info-value"><?php echo $timeRange; ?></div>
                                            </div>
                                            <div class="info-item">
                                                <div class="info-label">Người hủy</div>
                                                <div class="info-value"><?php echo htmlspecialchars($booking['canceled_by_name']); ?></div>
                                            </div>
                                            <div class="info-item">
                                                <div class="info-label">Thời gian hủy</div>
                                                <div class="info-value"><?php echo $canceledAt; ?></div>
                                            </div>
                                        </div>
                                        <div class="rejection-reason">
                                            <div class="rejection-label">LÝ DO HỦY:</div>
                                            <div class="rejection-text"><?php echo htmlspecialchars($booking['cancel_reason']); ?></div>
                                        </div>
                                        <button class="btn-detail" onclick="viewDetail('<?php echo htmlspecialchars($booking['booking_code']); ?>')">👁️ Xem chi tiết</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php include __DIR__ . '/../common/footer.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Chi tiết -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Chi tiết đơn đã hủy</div>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="detail-section">
                    <div class="section-title">
                        <span class="section-icon primary">📋</span>
                        Thông tin cơ bản
                    </div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Mã đơn đặt phòng</div>
                            <div class="detail-value" id="detailCode">--</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Trạng thái</div>
                            <div class="detail-value" id="detailStatus">--</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Mục đích sử dụng</div>
                            <div class="detail-value" id="detailPurpose">--</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Ghi chú</div>
                            <div class="detail-value" id="detailNotes">--</div>
                        </div>
                    </div>
                </div>
                <div class="detail-section">
                    <div class="section-title">
                        <span class="section-icon primary">🏠</span>
                        Thông tin phòng
                    </div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Mã phòng</div>
                            <div class="detail-value" id="detailRoomCode">--</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Tên phòng</div>
                            <div class="detail-value" id="detailRoomName">--</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Loại phòng</div>
                            <div class="detail-value" id="detailRoomType">--</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Sức chứa</div>
                            <div class="detail-value" id="detailCapacity">--</div>
                        </div>
                    </div>
                </div>
                <div class="detail-section">
                    <div class="section-title">
                        <span class="section-icon success">⏰</span>
                        Thời gian sử dụng
                    </div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Ngày đặt phòng</div>
                            <div class="detail-value" id="detailDate">--</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Giờ bắt đầu</div>
                            <div class="detail-value" id="detailStartTime">--</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Giờ kết thúc</div>
                            <div class="detail-value" id="detailEndTime">--</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Số người tham gia</div>
                            <div class="detail-value" id="detailParticipants">--</div>
                        </div>
                    </div>
                </div>
                <div class="detail-section" id="cancelInfoSection">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Người hủy</div>
                            <div class="detail-value" id="detailCanceledBy">--</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Thời gian hủy</div>
                            <div class="detail-value" id="detailCanceledAt">--</div>
                        </div>
                    </div>
                </div>
                <div class="detail-section" id="cancelReasonSection">
                    <div class="rejection-reason">
                        <div class="rejection-label">LÝ DO HỦY:</div>
                        <div class="rejection-text" id="detailCancelReason">--</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-modal secondary" onclick="closeModal()">Đóng</button>
            </div>
        </div>
    </div>
    <script>
        const canceledBookings = <?php echo json_encode($canceledBookings); ?>;
        function viewDetail(code) {
            const booking = canceledBookings.find(b => b.booking_code === code);
            if (!booking) return;
            document.getElementById('detailCode').textContent = booking.booking_code;
            document.getElementById('detailStatus').innerHTML = '<span class="status-badge canceled">🚫 Đã hủy</span>';
            document.getElementById('detailPurpose').textContent = booking.purpose || '--';
            document.getElementById('detailNotes').textContent = booking.notes || 'Không có';
            document.getElementById('detailRoomCode').textContent = booking.room_code;
            document.getElementById('detailRoomName').textContent = booking.room_name;
            document.getElementById('detailRoomType').textContent = booking.type_name;
            document.getElementById('detailCapacity').textContent = booking.capacity + ' người';
            document.getElementById('detailDate').textContent = new Date(booking.booking_date).toLocaleDateString('vi-VN');
            document.getElementById('detailStartTime').textContent = booking.start_time.substring(0,5);
            document.getElementById('detailEndTime').textContent = booking.end_time.substring(0,5);
            document.getElementById('detailParticipants').textContent = booking.participants + ' người';
            document.getElementById('detailCanceledBy').textContent = booking.canceled_by_name || '--';
            document.getElementById('detailCanceledAt').textContent = booking.canceled_at ? new Date(booking.canceled_at).toLocaleString('vi-VN') : '--';
            document.getElementById('detailCancelReason').textContent = booking.cancel_reason || '--';
            document.getElementById('detailModal').classList.add('active');
        }
        function closeModal() {
            document.getElementById('detailModal').classList.remove('active');
        }
        // Đóng modal khi click ra ngoài
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>