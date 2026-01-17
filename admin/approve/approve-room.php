<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}
include __DIR__ . '/../common/header.php';
require_once __DIR__ . '/../../helpers/helpers.php';
require_once __DIR__ . '/../common/paginate.php';
$userRole = $_SESSION['user_role'] ?? '';

// Kiểm tra quyền phê duyệt
if (!checkPermission($conn, $userRole, 'approve_booking')) {
    echo "<script>alert('Bạn không có quyền truy cập trang phê duyệt!'); window.location.href='../dashboard/index.php';</script>";
    exit;
}

require_once __DIR__ . '/../common/alert.php';

// Thông báo thành công
$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);
require_once __DIR__ . '/../common/alert.php';

// Hiển thị thông báo thành công nếu có
if (isset($_SESSION['success'])) {
    showSuccessNotification($_SESSION['success']);
    unset($_SESSION['success']);
}

// Lấy lỗi và dữ liệu cũ nếu có
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

// Xử lý bộ lọc và phân trang
$status_filter = $_GET['status'] ?? 'all';
$current_page = (int)($_GET['page'] ?? 1);
$limit = 10;

// Xây dựng điều kiện WHERE
$where_clause = "WHERE b.status != 'da_huy'";
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $db_status = '';
    switch($status_filter) {
        case 'pending': $db_status = 'cho_duyet'; break;
        case 'approved': $db_status = 'da_duyet'; break;
        case 'rejected': $db_status = 'tu_choi'; break;
    }
    if ($db_status) {
        $where_clause .= " AND b.status = ?";
        $params[] = $db_status;
        $types .= "s";
    }
}

// Đếm tổng số đơn để phân trang
$count_query = "SELECT COUNT(*) as total FROM bookings b $where_clause";
if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_items = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    $total_items = $conn->query($count_query)->fetch_assoc()['total'];
}

// Tính toán phân trang
$pagination = paginate($total_items, $current_page, $limit);

// Lấy dữ liệu đơn đặt phòng (có LIMIT/OFFSET)
$query = "SELECT b.*, u.full_name as user_name, u.email as user_email, u.phone as user_phone, u.department as user_department,
                 r.room_code, r.room_name, r.capacity, rt.type_name,
                 au.full_name as approved_by_name,
                 ru.full_name as rejected_by_name
          FROM bookings b
          JOIN users u ON b.user_id = u.id
          JOIN rooms r ON b.room_id = r.id
          JOIN room_types rt ON r.type_id = rt.id
          LEFT JOIN users au ON b.approved_by = au.id
          LEFT JOIN users ru ON b.rejected_by = ru.id
          $where_clause
          ORDER BY b.created_at DESC
          LIMIT ? OFFSET ?";

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

// Hàm lấy viết tắt tên
function getInitials($name) {
    $parts = explode(' ', $name);
    $initials = '';
    foreach ($parts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return substr($initials, -2);
}

// Hàm chuyển đổi status
function getStatusBadge($status) {
    switch($status) {
        case 'cho_duyet':
            return ['text' => '⏳ Chờ duyệt', 'class' => 'pending', 'badge' => 'pending'];
        case 'da_duyet':
            return ['text' => '✓ Đã duyệt', 'class' => 'approved', 'badge' => 'approved'];
        case 'tu_choi':
            return ['text' => '✕ Từ chối', 'class' => 'rejected', 'badge' => 'rejected'];
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
        SUM(CASE WHEN b.status = 'tu_choi' THEN 1 ELSE 0 END) as rejected
    FROM bookings b
    WHERE b.status != 'da_huy'
";
$counts = $conn->query($counts_query)->fetch_assoc();

$total_all = $counts['total'];
$pending = $counts['pending'];
$approved = $counts['approved'];
$rejected = $counts['rejected'];
?>
<style>
.avatar {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-initial {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    border-radius: 8px;
}

.bg-label-primary {
    background-color: #e7f1ff;
    color: #2563eb;
}

.bg-label-success {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.bg-label-warning {
    background-color: #fff3cd;
    color: #856404;
}

.bg-label-danger {
    background-color: #ffebee;
    color: #c62828;
}

.schedule-item {
    display: flex;
    align-items: center;
    padding: 16px;
    border-left: 4px solid #2563eb;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 12px;
}

.schedule-item.orange {
    border-left-color: #ff9800;
}

.schedule-item.green {
    border-left-color: #10b981;
}

.schedule-item-content {
    flex: 1;
}

.schedule-item-time {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}

.schedule-item-location {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    color: #999;
    margin-bottom: 4px;
}

.schedule-item-title {
    font-size: 13px;
    color: #666;
}

.schedule-item-status {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 20px;
}

.doughnut-container {
    position: relative;
    height: 320px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ============================== */

.container {
    max-width: 1400px;
    margin: 0 auto;
}

/* Header */
/* .header {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-bottom: 24px;
}

.header h1 {
    font-size: 28px;
    color: #1a202c;
    margin-bottom: 8px;
}

.header p {
    color: #718096;
    font-size: 14px;
} */

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border-left: 4px solid;
    transition: transform 0.2s, box-shadow 0.2s;
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

.stat-label {
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.stat-card.pending .stat-label {
    color: #d97706;
}

.stat-card.approved .stat-label {
    color: #059669;
}

.stat-card.rejected .stat-label {
    color: #dc2626;
}

.stat-number {
    font-size: 48px;
    font-weight: 700;
    line-height: 1;
}

.stat-card.pending .stat-number {
    color: #f59e0b;
}

.stat-card.approved .stat-number {
    color: #10b981;
}

.stat-card.rejected .stat-number {
    color: #ef4444;
}

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
}

.tab-btn:hover {
    background: #edf2f7;
}

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

.tab-btn.active .tab-badge {
    background: rgba(255, 255, 255, 0.25);
}

/* Booking Cards */
.bookings-list {
    display: grid;
    gap: 16px;
}

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

.booking-card.pending {
    border-left-color: #f59e0b;
}

.booking-card.approved {
    border-left-color: #10b981;
}

.booking-card.rejected {
    border-left-color: #ef4444;
}

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
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

.status-badge.pending {
    background: #fef3c7;
    color: #d97706;
}

.status-badge.approved {
    background: #d1fae5;
    color: #059669;
}

.status-badge.rejected {
    background: #fee2e2;
    color: #dc2626;
}

.booking-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-label {
    font-size: 12px;
    color: #718096;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 14px;
    color: #1a202c;
    font-weight: 500;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 4px;
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
}

.booking-actions {
    display: flex;
    gap: 12px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.btn {
    flex: 1;
    padding: 12px 24px;
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

.btn-approve {
    background: #10b981;
    color: white;
}

.btn-approve:hover {
    background: #059669;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
}

.btn-reject {
    background: #ef4444;
    color: white;
}

.btn-reject:hover {
    background: #dc2626;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
}

.btn-detail {
    background: #f7fafc;
    color: #4a5568;
    border: 1px solid #e2e8f0;
}

.btn-detail:hover {
    background: #edf2f7;
    border-color: #cbd5e0;
}

.rejection-reason {
    background: #fee2e2;
    border-left: 4px solid #ef4444;
    padding: 12px 16px;
    border-radius: 8px;
    margin-top: 16px;
}

.rejection-label {
    font-size: 12px;
    font-weight: 600;
    color: #dc2626;
    margin-bottom: 4px;
}

.rejection-text {
    font-size: 14px;
    color: #991b1b;
}

/* Modal Detail */
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
    animation: fadeIn 0.3s;
}

.modal-overlay.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

.modal-content {
    background: white;
    border-radius: 16px;
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
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

.modal-title {
    font-size: 24px;
    font-weight: 700;
}

.modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.modal-body {
    padding: 32px;
}

.detail-section {
    margin-bottom: 32px;
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.section-icon.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.section-icon.success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.section-icon.warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.section-icon.danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    background: #f9fafb;
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.detail-label {
    font-size: 12px;
    color: #6b7280;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: 15px;
    color: #1f2937;
    font-weight: 600;
}

.user-card {
    display: flex;
    align-items: center;
    gap: 16px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    margin-top: 12px;
}

.user-avatar-large {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 24px;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.user-details {
    flex: 1;
}

.user-name {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.user-role {
    display: inline-block;
    padding: 4px 12px;
    background: #dbeafe;
    color: #1e40af;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
}

.user-contact {
    font-size: 14px;
    color: #6b7280;
}

.room-preview {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px;
    border-radius: 12px;
    color: white;
    text-align: center;
    margin-top: 12px;
}

.room-code-large {
    font-size: 64px;
    font-weight: 700;
    margin-bottom: 8px;
    text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.room-name-large {
    font-size: 20px;
    opacity: 0.9;
}

.facilities-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.facility-tag {
    padding: 8px 16px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 20px;
    font-size: 13px;
    color: #4b5563;
    font-weight: 500;
}

.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 8px;
    bottom: 8px;
    width: 2px;
    background: #e5e7eb;
}

.timeline-item {
    position: relative;
    margin-bottom: 24px;
    padding: 16px 20px;
    background: #f9fafb;
    border-radius: 12px;
    border-left: 4px solid;
}

.timeline-item.created {
    border-left-color: #3b82f6;
}

.timeline-item.approved {
    border-left-color: #10b981;
}

.timeline-item.rejected {
    border-left-color: #ef4444;
}

.timeline-dot {
    position: absolute;
    left: -36px;
    top: 20px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 0 2px;
}

.timeline-item.created .timeline-dot {
    background: #3b82f6;
    box-shadow: 0 0 0 2px #3b82f6;
}

.timeline-item.approved .timeline-dot {
    background: #10b981;
    box-shadow: 0 0 0 2px #10b981;
}

.timeline-item.rejected .timeline-dot {
    background: #ef4444;
    box-shadow: 0 0 0 2px #ef4444;
}

.timeline-action {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.timeline-user {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 4px;
}

.timeline-time {
    font-size: 12px;
    color: #9ca3af;
}

.modal-footer {
    padding: 20px 32px;
    border-top: 2px solid #e2e8f0;
    display: flex;
    gap: 12px;
    background: #f9fafb;
    border-radius: 0 0 16px 16px;
}

.btn-large {
    flex: 1;
    padding: 14px 28px;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-close-modal {
    background: white;
    color: #4b5563;
    border: 2px solid #e5e7eb;
}

.btn-close-modal:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}
</style>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include __DIR__ . '/../common/menu-sidebar.php'; ?>
            <div class="layout-page">
                <?php include __DIR__ . '/../common/navbar.php'; ?>

                <div class="container">
                    <!-- Header -->
                    <div class="row mb-4 mt-4">
                        <div class="col-12">
                            <h4 class="mb-1">Phê duyệt đơn đặt phòng</h4>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="stats-grid">
                        <div class="stat-card pending">
                            <div class="stat-label">Chờ duyệt</div>
                            <div class="stat-number"><?php echo $pending; ?></div>
                        </div>

                        <div class="stat-card approved">
                            <div class="stat-label">Đã duyệt</div>
                            <div class="stat-number"><?php echo $approved; ?></div>
                        </div>

                        <div class="stat-card rejected">
                            <div class="stat-label">Từ chối</div>
                            <div class="stat-number"><?php echo $rejected; ?></div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="tabs">
                        <a href="?status=all" class="tab-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                            Tất cả <span class="tab-badge"><?php echo $total_all; ?></span>
                        </a>
                        <a href="?status=pending" class="tab-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                            Chờ duyệt <span class="tab-badge"><?php echo $pending; ?></span>
                        </a>
                        <a href="?status=approved" class="tab-btn <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                            Đã duyệt <span class="tab-badge"><?php echo $approved; ?></span>
                        </a>
                        <a href="?status=rejected" class="tab-btn <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                            Từ chối <span class="tab-badge"><?php echo $rejected; ?></span>
                        </a>
                    </div>

                    <!-- Bookings List -->
                    <div class="bookings-list" id="bookingsList">
                        <?php if (empty($bookings)): ?>
                        <div style="text-align: center; padding: 40px 20px; color: #999;">
                            <p>Không có đơn đặt phòng nào</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                        <?php
                                    $statusInfo = getStatusBadge($booking['status']);
                                    $userInitials = getInitials($booking['user_name']);
                                    $bookingDate = date('d/m/Y', strtotime($booking['booking_date']));
                                    $timeRange = substr($booking['start_time'], 0, 5) . ' - ' . substr($booking['end_time'], 0, 5);
                                ?>
                        <div class="booking-card <?php echo $statusInfo['class']; ?>"
                            data-status="<?php echo $booking['status']; ?>">
                            <div class="booking-header">
                                <div class="booking-code"><?php echo htmlspecialchars($booking['booking_code']); ?>
                                </div>
                                <span
                                    class="status-badge <?php echo $statusInfo['badge']; ?>"><?php echo $statusInfo['text']; ?></span>
                            </div>

                            <div class="booking-info">
                                <div class="info-item">
                                    <div class="info-label">Người đặt</div>
                                    <div class="user-info">
                                        <div class="user-avatar"><?php echo $userInitials; ?></div>
                                        <span><?php echo htmlspecialchars($booking['user_name']); ?></span>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Phòng</div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($booking['room_code']); ?><br><small
                                            style="color: #718096;"><?php echo htmlspecialchars($booking['room_name']); ?></small>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Ngày & Giờ</div>
                                    <div class="info-value">
                                        <?php echo $bookingDate; ?><br><?php echo substr($booking['start_time'], 0, 5) . ' - ' . substr($booking['end_time'], 0, 5); ?>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Mục đích</div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars(substr($booking['purpose'], 0, 50)); ?>...</div>
                                </div>
                            </div>

                            <?php if ($booking['status'] === 'tu_choi' && $booking['rejection_reason']): ?>
                            <div class="rejection-reason">
                                <div class="rejection-label">LÝ DO TỪ CHỐI:</div>
                                <div class="rejection-text">
                                    <?php echo htmlspecialchars($booking['rejection_reason']); ?></div>
                            </div>
                            <?php endif; ?>

                            <div class="booking-actions">
                                <button class="btn btn-detail"
                                    onclick="viewDetail('<?php echo htmlspecialchars($booking['booking_code']); ?>', '<?php echo $booking['status']; ?>')">
                                    👁 Chi tiết
                                </button>
                                <?php if ($booking['status'] === 'cho_duyet'): ?>
                                <form method="POST" action="handle-approve-room.php" style="flex: 1;">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="booking_code"
                                        value="<?php echo htmlspecialchars($booking['booking_code']); ?>">
                                    <button type="button" class="btn btn-approve btn-confirm-approve" style="width: 100%;">
                                        ✓ Phê duyệt
                                    </button>
                                </form>
                                <button class="btn btn-reject"
                                    onclick="rejectBooking('<?php echo htmlspecialchars($booking['booking_code']); ?>')">
                                    ✕ Từ chối
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Phân trang -->
                        <div class="mt-4">
                            <?php echo renderPagination($pagination['total_pages'], $pagination['current_page'], $_SERVER['REQUEST_URI']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Modal Chi tiết -->
                <div class="modal-overlay" id="detailModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="modal-title" id="modalTitle">Chi tiết đơn đặt phòng</div>
                            <button class="modal-close" onclick="closeModal()">×</button>
                        </div>

                        <div class="modal-body">
                            <!-- Thông tin cơ bản -->
                            <div class="detail-section">
                                <div class="section-title">
                                    <div class="section-icon primary">📋</div>
                                    Thông tin đơn đặt phòng
                                </div>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <div class="detail-label">Mã đơn</div>
                                        <div class="detail-value" id="detailCode"></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Trạng thái</div>
                                        <div id="detailStatus"></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Ngày đặt</div>
                                        <div class="detail-value" id="detailCreatedAt"></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Số người</div>
                                        <div class="detail-value" id="detailParticipants"></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Ngày sử dụng</div>
                                        <div class="detail-value" id="detailDate"></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Thời gian</div>
                                        <div class="detail-value" id="detailTime"></div>
                                    </div>
                                    <div class="detail-item" style="grid-column: 1 / -1;">
                                        <div class="detail-label">Mục đích</div>
                                        <div class="detail-value" id="detailPurpose"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Thông tin người đặt -->
                            <div class="detail-section">
                                <div class="section-title">
                                    <div class="section-icon success">👤</div>
                                    Thông tin người đặt
                                </div>
                                <div class="user-card">
                                    <div class="user-avatar-large" id="detailUserAvatar">NA</div>
                                    <div class="user-details">
                                        <div class="user-name" id="detailUserName"></div>
                                        <div class="user-role" id="detailUserRole"></div>
                                        <div class="user-contact" id="detailUserEmail"></div>
                                        <div class="user-contact" id="detailUserPhone"></div>
                                        <div class="user-contact" id="detailUserDepartment"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Thông tin phòng -->
                            <div class="detail-section">
                                <div class="section-title">
                                    <div class="section-icon warning">🏫</div>
                                    Thông tin phòng
                                </div>
                                <div class="room-preview">
                                    <div class="room-code-large" id="detailRoomCode">A201</div>
                                    <div class="room-name-large" id="detailRoomName">Lab CNTT 01</div>
                                </div>
                                <div class="detail-grid" style="margin-top: 16px;">
                                    <div class="detail-item">
                                        <div class="detail-label">Loại phòng</div>
                                        <div class="detail-value" id="detailRoomType"></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Sức chứa</div>
                                        <div class="detail-value" id="detailRoomCapacity"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lịch sử phê duyệt -->
                            <div class="detail-section">
                                <div class="section-title">
                                    <div class="section-icon danger">📜</div>
                                    Lịch sử phê duyệt
                                </div>
                                <div class="timeline" id="detailTimeline">
                                </div>
                            </div>

                            <!-- Lý do từ chối (nếu có) -->
                            <div class="detail-section" id="rejectionSection" style="display: none;">
                                <div class="rejection-reason">
                                    <div class="rejection-label">LÝ DO TỪ CHỐI:</div>
                                    <div class="rejection-text" id="detailRejectionReason"></div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer" id="modalFooter">
                            <button class="btn-large btn-close-modal" onclick="closeModal()">
                                Đóng
                            </button>
                            <form method="POST" action="handle-approve-room.php" id="approveForm"
                                style="flex: 1; display: none;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" id="approveBookingCode" name="booking_code">
                                <button type="submit" class="btn-large btn-approve btn-confirm-approve" style="width: 100%;">
                                    ✓ Phê duyệt
                                </button>
                            </form>
                            <button class="btn-large btn-reject" id="btnModalReject" onclick="rejectBooking()"
                                style="display: none; flex: 1;">
                                ✕ Từ chối
                            </button>
                        </div>
                    </div>
                </div>

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
    let currentBookingCode = '';
    let currentBookingData = {};

    const bookingDataFromDB = <?php echo json_encode($bookings); ?>;

    // Hàm filter cũ dùng JS đã được bỏ vì dùng phân trang Server-side

    function viewDetail(code, status) {
        currentBookingCode = code;

        const booking = bookingDataFromDB.find(b => b.booking_code === code);
        if (!booking) return;

        currentBookingData = booking;
        // console.log(booking);

        // Update modal content
        document.getElementById('modalTitle').textContent = `Chi tiết đơn ${code}`;
        document.getElementById('detailCode').textContent = booking.booking_code;

        // Status badge
        let statusText = '';
        let statusClass = '';
        if (booking.status === 'cho_duyet') {
            statusText = '⏳ Chờ duyệt';
            statusClass = 'pending';
        } else if (booking.status === 'da_duyet') {
            statusText = '✓ Đã duyệt';
            statusClass = 'approved';
        } else {
            statusText = '✕ Từ chối';
            statusClass = 'rejected';
        }
        document.getElementById('detailStatus').innerHTML =
            `<span class="status-badge ${statusClass}">${statusText}</span>`;

        // User info
        const userInitials = booking.user_name.split(' ').map(n => n[0].toUpperCase()).join('').slice(-2);
        document.getElementById('detailUserAvatar').textContent = userInitials;
        document.getElementById('detailUserName').textContent = booking.user_name;
        document.getElementById('detailUserRole').textContent = '👤 Người dùng';
        document.getElementById('detailUserEmail').textContent = booking.user_email;
        document.getElementById('detailUserPhone').textContent = booking.user_phone;
        document.getElementById('detailUserDepartment').textContent = booking.user_department;

        // Room info
        document.getElementById('detailRoomCode').textContent = booking.room_code;
        document.getElementById('detailRoomName').textContent = booking.room_name;
        document.getElementById('detailRoomType').textContent = booking.room_type;
        document.getElementById('detailRoomCapacity').textContent = booking.capacity + ' người';

        // Booking info
        document.getElementById('detailDate').textContent = new Date(booking.booking_date).toLocaleDateString('vi-VN');
        document.getElementById('detailTime').textContent = booking.start_time.substring(0, 5) + ' - ' + booking
            .end_time.substring(0, 5);
        document.getElementById('detailParticipants').textContent = booking.participants || 'N/A';
        document.getElementById('detailPurpose').textContent = booking.purpose;
        document.getElementById('detailCreatedAt').textContent = new Date(booking.created_at).toLocaleDateString(
            'vi-VN');

        // Timeline
        let timelineHTML = `
            <div class="timeline-item created">
                <div class="timeline-dot"></div>
                <div class="timeline-action">🆕 Tạo đơn đặt phòng</div>
                <div class="timeline-user">Bởi: ${booking.user_name}</div>
                <div class="timeline-time">${new Date(booking.created_at).toLocaleString('vi-VN')}</div>
            </div>
        `;

        if (booking.status === 'da_duyet' && booking.approved_at) {
            timelineHTML += `
                <div class="timeline-item approved">
                    <div class="timeline-dot"></div>
                    <div class="timeline-action"> Đã phê duyệt</div>
                    <div class="timeline-user">Người duyệt: ${booking.approved_by_name || 'Hệ thống'}</div>
                    <div class="timeline-time">${new Date(booking.approved_at).toLocaleString('vi-VN')}</div>
                </div>
            `;
        } else if (booking.status === 'tu_choi' && booking.rejected_at) {
            timelineHTML += `
                <div class="timeline-item rejected">
                    <div class="timeline-dot"></div>
                    <div class="timeline-action">✕ Đã từ chối</div>
                    <div class="timeline-user">Người từ chối: ${booking.rejected_by_name || 'Hệ thống'}</div>
                    <div class="timeline-time">${new Date(booking.rejected_at).toLocaleString('vi-VN')}</div>
                </div>
            `;
        }

        document.getElementById('detailTimeline').innerHTML = timelineHTML;

        // Rejection reason
        if (booking.status === 'tu_choi' && booking.rejection_reason) {
            document.getElementById('rejectionSection').style.display = 'block';
            document.getElementById('detailRejectionReason').textContent = booking.rejection_reason;
        } else {
            document.getElementById('rejectionSection').style.display = 'none';
        }

        // Footer buttons
        const approveForm = document.getElementById('approveForm');
        const btnReject = document.getElementById('btnModalReject');

        if (booking.status === 'cho_duyet') {
            approveForm.style.display = 'flex';
            btnReject.style.display = 'flex';
            document.getElementById('approveBookingCode').value = code;
        } else {
            approveForm.style.display = 'none';
            btnReject.style.display = 'none';
        }

        // Show modal
        document.getElementById('detailModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('detailModal').classList.remove('active');
    }

    function rejectBooking(currentBookingCode) {
        setupRejectConfirmation(currentBookingCode);
    }

    // Close modal when clicking outside
    document.getElementById('detailModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        setupApproveConfirmation('.btn-confirm-approve');
    });
    </script>
</body>

</html>