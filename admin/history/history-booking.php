<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
include __DIR__ . '/../common/header.php';
require_once __DIR__ . '/../../helpers/helpers.php';
require_once __DIR__ . '/../common/paginate.php';
$userRole = $_SESSION['user_role'] ?? '';

// Kiểm tra quyền xem lịch sử
if (!checkPermission($conn, $userRole, 'view_history')) {
    echo "<script>alert('Bạn không có quyền xem lịch sử!'); window.location.href='../dashboard/index.php';</script>";
    exit;
}

require_once __DIR__ . '/../common/alert.php';

// Lấy thông báo thành công
if (isset($_SESSION['success'])) {
    showSuccessAlert($_SESSION['success']);
    unset($_SESSION['success']);
}

// Xử lý bộ lọc và phân trang
$status_filter = $_GET['status'] ?? 'all';
$current_page = (int)($_GET['page'] ?? 1);
$limit = 10;

// Xây dựng điều kiện WHERE
$where_clause = "WHERE b.user_id = ?";
$params = [$user_id];
$types = "i";

if ($status_filter !== 'all') {
    $db_status = '';
    switch($status_filter) {
        case 'pending': $db_status = 'cho_duyet'; break;
        case 'approved': $db_status = 'da_duyet'; break;
        case 'rejected': $db_status = 'tu_choi'; break;
        case 'canceled': $db_status = 'da_huy'; break;
    }
    if ($db_status) {
        $where_clause .= " AND b.status = ?";
        $params[] = $db_status;
        $types .= "s";
    }
}

// Đếm tổng số đơn để phân trang
$count_query = "SELECT COUNT(*) as total FROM bookings b $where_clause";
$stmt = $conn->prepare($count_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_items = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Tính toán phân trang
$pagination = paginate($total_items, $current_page, $limit);

// Lấy danh sách đơn đặt phòng (có LIMIT/OFFSET)
$query = "
    SELECT
        b.*,
        r.room_code,
        r.room_name,
        rt.type_name,
        r.capacity,
        u.full_name AS canceled_by_name,
        au.full_name AS approved_by_name,
        ru.full_name AS rejected_by_name
    FROM bookings b
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN room_types rt ON r.type_id = rt.id
    LEFT JOIN users u ON b.canceled_by = u.id
    LEFT JOIN users au ON b.approved_by = au.id
    LEFT JOIN users ru ON b.rejected_by = ru.id
    $where_clause
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
";

$params_with_limit = array_merge($params, [$pagination['limit'], $pagination['offset']]);
$types_with_limit = $types . "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types_with_limit, ...$params_with_limit);
$stmt->execute();
$result = $stmt->get_result();
$bookings = [];

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();

// Hàm chuyển đổi status
function getStatusBadge($status) {
    switch($status) {
        case 'cho_duyet':
            return ['text' => '⏳ Chờ duyệt', 'class' => 'pending', 'badge' => 'pending'];
        case 'da_duyet':
            return ['text' => '✓ Đã duyệt', 'class' => 'approved', 'badge' => 'approved'];
        case 'tu_choi':
            return ['text' => '✕ Từ chối', 'class' => 'rejected', 'badge' => 'rejected'];
        case 'da_huy':
            return ['text' => '🚫 Đã hủy', 'class' => 'canceled', 'badge' => 'canceled'];
        default:
            return ['text' => 'Không xác định', 'class' => 'pending', 'badge' => 'pending'];
    }
}

// Đếm số đơn theo trạng thái (Tổng cộng, không phân trang)
$counts_query = "
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN b.status = 'cho_duyet' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN b.status = 'da_duyet' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN b.status = 'tu_choi' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN b.status = 'da_huy' THEN 1 ELSE 0 END) as canceled
    FROM bookings b
    WHERE b.user_id = ?
";
$stmt = $conn->prepare($counts_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$counts = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_all = $counts['total'];
$pending = $counts['pending'];
$approved = $counts['approved'];
$rejected = $counts['rejected'];
$canceled = $counts['canceled'];
?>

<style>
/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border-left: 4px solid;
    transition: transform 0.2s, box-shadow 0.2s;
    text-align: center;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.stat-card.pending {
    border-left-color: #f59e0b;
    background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
}

.stat-card.approved {
    border-left-color: #10b981;
    background: linear-gradient(135deg, #e6fff5 0%, #ffffff 100%);
}

.stat-card.rejected {
    border-left-color: #ef4444;
    background: linear-gradient(135deg, #ffe6e6 0%, #ffffff 100%);
}

.stat-card.canceled {
    border-left-color: #6b7280;
    background: linear-gradient(135deg, #f3f4f6 0%, #ffffff 100%);
}

.stat-label {
    font-size: 13px;
    color: #666;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.stat-number {
    font-size: 42px;
    font-weight: 700;
    line-height: 1;
}

.stat-card.pending .stat-number { color: #f59e0b; }
.stat-card.approved .stat-number { color: #10b981; }
.stat-card.rejected .stat-number { color: #ef4444; }
.stat-card.canceled .stat-number { color: #6b7280; }

/* Tabs */
.tabs {
    background: white;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-bottom: 24px;
    display: flex;
    gap: 12px;
    overflow-x: auto;
}

.tab-btn {
    padding: 12px 24px;
    border: none;
    background: #f7fafc;
    color: #4a5568;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    white-space: nowrap;
    text-decoration: none;
}

.tab-btn:hover { background: #edf2f7; }
.tab-btn.active {
    background: #3b82f6;
    color: white;
    box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
}

.tab-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.3);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    margin-left: 8px;
}

/* Bookings List */
.bookings-list { display: grid; gap: 16px; }

.booking-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    padding: 24px;
    transition: transform 0.2s, box-shadow 0.2s;
    border-left: 4px solid #e2e8f0;
}

.booking-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.booking-card.pending { border-left-color: #f59e0b; }
.booking-card.approved { border-left-color: #10b981; }
.booking-card.rejected { border-left-color: #ef4444; }
.booking-card.canceled { border-left-color: #6b7280; }

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}

.booking-code {
    font-size: 20px;
    font-weight: 700;
    color: #1a202c;
}

.status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.pending { background: #fef3c7; color: #d97706; }
.status-badge.approved { background: #d1fae5; color: #059669; }
.status-badge.rejected { background: #fee2e2; color: #dc2626; }
.status-badge.canceled { background: #f3f4f6; color: #6b7280; }

.booking-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.info-item { display: flex; flex-direction: column; gap: 4px; }
.info-label {
    font-size: 12px;
    color: #718096;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}
.info-value { font-size: 14px; color: #1a202c; font-weight: 600; }

/* Pagination Styling */
.pagination-rounded .page-link {
    border-radius: 8px !important;
    margin: 0 3px;
    border: none;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4a5568;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.pagination-rounded .page-item.active .page-link {
    background: #3b82f6;
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.booking-actions {
    display: flex;
    gap: 12px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.btn-card {
    flex: 1;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-card-detail { background: #3b82f6; color: white; }
.btn-card-detail:hover { background: #2563eb; }
.btn-card-cancel { background: #ef4444; color: white; }
.btn-card-cancel:hover { background: #dc2626; }

.rejection-reason {
    background: #fee2e2;
    border-left: 4px solid #ef4444;
    padding: 12px 16px;
    border-radius: 8px;
    margin-top: 16px;
}

.rejection-label { font-size: 12px; font-weight: 600; color: #dc2626; margin-bottom: 4px; }
.rejection-text { font-size: 14px; color: #991b1b; }

.export-bar { display: flex; gap: 10px; margin-bottom: 20px; }
</style>

<style>
/* Modal & Other Styles */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 1000;
    padding: 20px;
    overflow-y: auto;
}
.modal-overlay.active { display: flex; align-items: center; justify-content: center; }
.modal-content {
    background: white;
    border-radius: 16px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}
.modal-header {
    padding: 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px 16px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-close { background: none; border: none; color: white; font-size: 24px; cursor: pointer; }
.modal-body { padding: 32px; }
.detail-section { margin-bottom: 32px; }
.section-title { font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; background: #f9fafb; padding: 20px; border-radius: 12px; }
.detail-item { display: flex; flex-direction: column; gap: 6px; }
.detail-label { font-size: 12px; color: #6b7280; font-weight: 600; }
.detail-value { font-size: 15px; font-weight: 600; }
.modal-footer { padding: 20px 32px; background: #f9fafb; border-radius: 0 0 16px 16px; display: flex; }
.btn-modal { flex: 1; padding: 14px 28px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
.btn-modal.secondary { background: white; border: 2px solid #e5e7eb; }
.empty-state { text-align: center; padding: 60px 20px; }
</style>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include __DIR__ . '/../common/menu-sidebar.php'; ?>
            <div class="layout-page">
                <?php include __DIR__ . '/../common/navbar.php'; ?>

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <!-- Header -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="mb-1">📋 Lịch sử đặt phòng của tôi</h4>
                                <p class="text-muted mb-0">Quản lý và theo dõi các đơn đặt phòng của bạn</p>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="stats-grid">
                            <div class="stat-card pending"><div class="stat-label">Chờ duyệt</div><div class="stat-number"><?php echo $pending; ?></div></div>
                            <div class="stat-card approved"><div class="stat-label">Đã duyệt</div><div class="stat-number"><?php echo $approved; ?></div></div>
                            <div class="stat-card rejected"><div class="stat-label">Từ chối</div><div class="stat-number"><?php echo $rejected; ?></div></div>
                            <div class="stat-card canceled"><div class="stat-label">Hủy</div><div class="stat-number"><?php echo $canceled; ?></div></div>
                        </div>

                        <!-- Action Bar for Export -->
                        <div class="export-bar">
                            <a href="export-bookings.php?type=excel&status=<?php echo htmlspecialchars($status_filter); ?>" class="btn btn-success btn-sm">
                                <i class="bx bxs-file-export me-1"></i> Xuất Excel
                            </a>
                            <a href="export-bookings.php?type=pdf&status=<?php echo htmlspecialchars($status_filter); ?>" class="btn btn-danger btn-sm" target="_blank">
                                <i class="bx bxs-file-pdf me-1"></i> Xuất PDF
                            </a>
                        </div>

                        <!-- Tabs -->
                        <div class="tabs">
                            <a href="?status=all" class="tab-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">Tất cả <span class="tab-badge"><?php echo $total_all; ?></span></a>
                            <a href="?status=pending" class="tab-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Chờ duyệt <span class="tab-badge"><?php echo $pending; ?></span></a>
                            <a href="?status=approved" class="tab-btn <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">Đã duyệt <span class="tab-badge"><?php echo $approved; ?></span></a>
                            <a href="?status=rejected" class="tab-btn <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">Từ chối <span class="tab-badge"><?php echo $rejected; ?></span></a>
                            <a href="?status=canceled" class="tab-btn <?php echo $status_filter === 'canceled' ? 'active' : ''; ?>">Hủy <span class="tab-badge"><?php echo $canceled; ?></span></a>
                        </div>

                        <!-- Bookings List -->
                        <div class="bookings-list" id="bookingsList">
                            <?php if (empty($bookings)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">📦</div>
                                    <div class="empty-state-text">Không có đơn đặt phòng nào</div>
                                    <p class="text-muted">Hãy <a href="../../admin/booking/booking-room.php">đặt phòng</a> để bắt đầu</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                    <?php
                                        $statusInfo = getStatusBadge($booking['status']);
                                        $bookingDate = date('d/m/Y', strtotime($booking['booking_date']));
                                        $timeRange = substr($booking['start_time'], 0, 5) . ' - ' . substr($booking['end_time'], 0, 5);
                                        $createdDate = date('d/m/Y H:i', strtotime($booking['created_at']));
                                    ?>
                                    <div class="booking-card <?php echo $statusInfo['class']; ?>" data-status="<?php echo $booking['status']; ?>">
                                        <div class="booking-header">
                                            <div>
                                                <div class="booking-code"><?php echo htmlspecialchars($booking['booking_code']); ?></div>
                                                <p class="text-muted mb-0" style="font-size: 12px;">Tạo lúc: <?php echo $createdDate; ?></p>
                                            </div>
                                            <span class="status-badge <?php echo $statusInfo['badge']; ?>"><?php echo $statusInfo['text']; ?></span>
                                        </div>
                                        <div class="booking-grid">
                                            <div class="info-item"><div class="info-label">🏠 Phòng</div><div class="info-value"><?php echo htmlspecialchars($booking['room_code']); ?> - <?php echo htmlspecialchars($booking['room_name']); ?></div></div>
                                            <div class="info-item"><div class="info-label">📅 Ngày đặt</div><div class="info-value"><?php echo $bookingDate; ?></div></div>
                                            <div class="info-item"><div class="info-label">⏰ Thời gian</div><div class="info-value"><?php echo $timeRange; ?></div></div>
                                            <div class="info-item"><div class="info-label">👥 Số người</div><div class="info-value"><?php echo $booking['participants']; ?> người</div></div>
                                        </div>
                                        <?php if ($booking['status'] === 'tu_choi' && $booking['rejection_reason']): ?>
                                            <div class="rejection-reason"><div class="rejection-label">LÝ DO TỪ CHỐI:</div><div class="rejection-text"><?php echo htmlspecialchars($booking['rejection_reason']); ?></div></div>
                                        <?php endif; ?>
                                        <div class="booking-actions">
                                            <button class="btn-card btn-card-detail" onclick="viewDetail('<?php echo htmlspecialchars($booking['booking_code']); ?>')">👁️ Xem chi tiết</button>
                                            <?php if ($booking['status'] === 'cho_duyet'): ?>
                                                <button class="btn-card btn-card-cancel" onclick="cancelBooking('<?php echo htmlspecialchars($booking['booking_code']); ?>')">❌ Hủy</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="mt-4">
                                    <?php echo renderPagination($pagination['total_pages'], $pagination['current_page'], $_SERVER['REQUEST_URI']); ?>
                                </div>
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
            <div class="modal-header"><div class="modal-title">Chi tiết đơn đặt phòng</div><button class="modal-close" onclick="closeModal()">×</button></div>
            <div class="modal-body">
                <!-- Sections simplified for brevity in this rewrite, same IDs as before -->
                <div class="detail-section">
                    <div class="section-title">📋 Thông tin cơ bản</div>
                    <div class="detail-grid">
                        <div class="detail-item"><div class="detail-label">Mã đơn</div><div class="detail-value" id="detailCode">--</div></div>
                        <div class="detail-item"><div class="detail-label">Trạng thái</div><div class="detail-value" id="detailStatus">--</div></div>
                        <div class="detail-item"><div class="detail-label">Mục đích</div><div class="detail-value" id="detailPurpose">--</div></div>
                        <div class="detail-item"><div class="detail-label">Ghi chú</div><div class="detail-value" id="detailNotes">--</div></div>
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

                <div class="detail-section" id="rejectionSection" style="display: none;">
                    <div class="rejection-reason">
                        <div class="rejection-label" id="rejectionLabel">LÝ DO TỪ CHỐI:</div>
                        <div class="rejection-text" id="detailRejectionReason">--</div>
                    </div>
                </div>

                <div class="detail-section" id="cancelInfoSection" style="display: none;">
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

                <div class="detail-section" id="approvalInfoSection" style="display: none;">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Người duyệt</div>
                            <div class="detail-value" id="detailApprovedBy">--</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Thời gian duyệt</div>
                            <div class="detail-value" id="detailApprovedAt">--</div>
                        </div>
                    </div>
                </div>

                <div class="detail-section" id="rejectionInfoSection" style="display: none;">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Người từ chối</div>
                            <div class="detail-value" id="detailRejectedBy">--</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Thời gian từ chối</div>
                            <div class="detail-value" id="detailRejectedAt">--</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-modal secondary" onclick="closeModal()">Đóng</button>
            </div>
        </div>
    </div>

    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../../assets/vendor/js/menu.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const bookingsData = <?php echo json_encode($bookings); ?>;

        function viewDetail(code) {
            const booking = bookingsData.find(b => b.booking_code === code);
            if (!booking) return;

            document.getElementById('detailCode').textContent = booking.booking_code;
            document.getElementById('detailPurpose').textContent = booking.purpose;
            document.getElementById('detailNotes').textContent = booking.notes || 'Không có';
            document.getElementById('detailRoomCode').textContent = booking.room_code;
            document.getElementById('detailRoomName').textContent = booking.room_name;
            document.getElementById('detailRoomType').textContent = booking.type_name;
            document.getElementById('detailCapacity').textContent = booking.capacity + ' người';

            const date = new Date(booking.booking_date);
            document.getElementById('detailDate').textContent = date.toLocaleDateString('vi-VN');
            document.getElementById('detailStartTime').textContent = booking.start_time.substring(0, 5);
            document.getElementById('detailEndTime').textContent = booking.end_time.substring(0, 5);
            document.getElementById('detailParticipants').textContent = booking.participants + ' người';

            if (booking.status === 'tu_choi') {
                document.getElementById('rejectionSection').style.display = 'block';
                document.getElementById('rejectionLabel').textContent = 'LÝ DO TỪ CHỐI:';
                document.getElementById('detailRejectionReason').textContent = booking.rejection_reason;
            } else if (booking.status === 'da_huy') {
                document.getElementById('rejectionSection').style.display = 'block';
                document.getElementById('rejectionLabel').textContent = 'LÝ DO HỦY:';
                document.getElementById('detailRejectionReason').textContent = booking.cancel_reason;
            } else {
                document.getElementById('rejectionSection').style.display = 'none';
            }

            // Canceled info
            if (booking.canceled_by_name && booking.canceled_at) {
                document.getElementById('cancelInfoSection').style.display = 'block';
                document.getElementById('detailCanceledBy').textContent = booking.canceled_by_name;
                document.getElementById('detailCanceledAt').textContent = new Date(booking.canceled_at).toLocaleString('vi-VN');
            } else {
                document.getElementById('cancelInfoSection').style.display = 'none';
            }

            // Approval info
            if (booking.status === 'da_duyet' && booking.approved_by_name && booking.approved_at) {
                document.getElementById('approvalInfoSection').style.display = 'block';
                document.getElementById('detailApprovedBy').textContent = booking.approved_by_name;
                document.getElementById('detailApprovedAt').textContent = new Date(booking.approved_at).toLocaleString('vi-VN');
            } else {
                document.getElementById('approvalInfoSection').style.display = 'none';
            }

            // Rejection info
            if (booking.status === 'tu_choi' && booking.rejected_by_name && booking.rejected_at) {
                document.getElementById('rejectionInfoSection').style.display = 'block';
                document.getElementById('detailRejectedBy').textContent = booking.rejected_by_name;
                document.getElementById('detailRejectedAt').textContent = new Date(booking.rejected_at).toLocaleString('vi-VN');
            } else {
                document.getElementById('rejectionInfoSection').style.display = 'none';
            }

            document.getElementById('detailModal').classList.add('active');
        }
        function closeModal() { document.getElementById('detailModal').classList.remove('active'); }
        function cancelBooking(code) {
            Swal.fire({
                title: 'Hủy đơn?',
                input: 'textarea',
                inputPlaceholder: 'Nhập lý do hủy...',
                showCancelButton: true,
                confirmButtonText: 'Xác nhận',
                preConfirm: (reason) => {
                    if (!reason) { Swal.showValidationMessage('Vui lòng nhập lý do!'); }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('handle-cancel-booking.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'booking_code=' + encodeURIComponent(code) + '&cancel_reason=' + encodeURIComponent(result.value)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) { Swal.fire('Thành công', data.message, 'success').then(() => location.reload()); }
                        else { Swal.fire('Lỗi', data.message, 'error'); }
                    });
                }
            });
        }
    </script>
</body>
</html>