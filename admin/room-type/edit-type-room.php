<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../../config/connect.php';
require_once __DIR__ . '/../../helpers/helpers.php';

$userRole = $_SESSION['user_role'] ?? '';

// Kiểm tra quyền sửa loại phòng
if (!checkPermission($conn, $userRole, 'edit_room_type')) {
    echo "<script>alert('Bạn không có quyền chỉnh sửa loại phòng!'); window.location.href='../dashboard/index.php';</script>";
    exit;
}

// Lấy id loại phòng
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: list-type-room.php');
    exit;
}

// Lấy lỗi và dữ liệu cũ nếu có
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

// Nếu không có dữ liệu cũ (tức là lần đầu vào), lấy từ DB
if (empty($old)) {
    $stmt = $conn->prepare("SELECT * FROM room_types WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $roomType = $result->fetch_assoc();
    $stmt->close();

    if (!$roomType) {
        header('Location: list-type-room.php');
        exit;
    }
    $old = $roomType;
}

include __DIR__ . '/../common/header.php';
?>
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }
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
    background-color: #eff6ff;
    color: #3b82f6;
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
    border-left: 4px solid #3b82f6;
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
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="mb-1">Chỉnh sửa loại phòng</h4>
                            </div>
                        </div>
                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <form method="POST" action="process-edit-type-room.php">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                                    <div class="row">
                                        <div class="mb-3 col-md-6">
                                            <label for="type_name" class="form-label">Tên loại phòng</label>
                                            <input class="form-control" type="text" id="type_name" name="type_name"
                                                value="<?= htmlspecialchars($old['type_name'] ?? '') ?>" required />
                                        </div>
                                        <div class="mb-3 col-md-12">
                                            <label for="description" class="form-label">Mô tả</label>
                                            <textarea class="form-control" id="description" name="description"
                                                rows="2"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                                        </div>
                                        <!-- <div class="mb-3 col-md-6">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" id="requires_approval"
                                                    name="requires_approval" value="1"
                                                    <?= (isset($old['requires_approval']) && $old['requires_approval']) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="requires_approval">
                                                    Cần phê duyệt trước khi đặt
                                                </label>
                                            </div>
                                        </div> -->
                                    </div>
                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-primary me-2" style="background: var(--primary-gradient); border: none; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">Lưu</button>
                                        <a href="list-type-room.php" class="btn btn-outline-secondary">Hủy</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php include __DIR__ . '/../common/footer.php'; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
    <!-- <script src="../../assets/vendor/libs/popper/popper.js"></script> -->
    <script src="../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../../assets/vendor/js/menu.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>

</html>