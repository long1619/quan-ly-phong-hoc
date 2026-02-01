<?php
    session_start();

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../auth/login.php');
        exit;
    }

    include __DIR__ . '/../common/header.php';
    require_once __DIR__ . '/../../config/connect.php';

    // Lấy room id từ URL
    $roomId = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($roomId <= 0) {
        echo '<div class="alert alert-danger">Không tìm thấy phòng.</div>';
        exit;
    }

    // Lấy thông tin phòng và loại phòng
    $stmt = $conn->prepare("
        SELECT r.*, rt.type_name, rt.description as type_description
        FROM rooms r
        LEFT JOIN room_types rt ON r.type_id = rt.id
        WHERE r.id = ?
    ");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$room) {
        echo '<div class="alert alert-danger">Không tìm thấy phòng.</div>';
        exit;
    }

    // Chuyển đổi facilities từ JSON sang chuỗi để hiển thị
    $facilities_display = '';
    if (!empty($room['facilities'])) {
        $facilities_array = json_decode($room['facilities'], true);
        if (is_array($facilities_array) && count($facilities_array) > 0) {
            $facilities_display = implode(', ', $facilities_array);
        } else {
            $facilities_display = 'Chưa có thiết bị';
        }
    } else {
        $facilities_display = 'Chưa có thiết bị';
    }

    // Format trạng thái
    $status_map = [
        'trong' => 'Trống',
        'dang_su_dung' => 'Đang sử dụng',
        'bao_tri' => 'Bảo trì'
    ];
    $status_display = $status_map[$room['status']] ?? $room['status'];

?>
<style>
.profile-cover {
    height: 80px;
    background: linear-gradient(90deg, #b2dfdb 20%, #ffe0b2 60%, #ffcdd2 100%);
    border-radius: 8px 8px 0 0;
}

.profile-info {
    margin-left: 1%;
}

.profile-header {
    display: flex;
    align-items: center;
    padding: 24px 24px 24px 24px;
    background: #fff;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

.profile-avatar {
    width: 150px;
    height: 150px;
    border-radius: 16px;
    object-fit: cover;
    border: 4px solid #fff;
    margin-right: 32px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.profile-info h3 {
    margin-bottom: 4px;
    font-weight: 600;
}

.profile-info .meta {
    color: #888;
    font-size: 15px;
    display: flex;
    gap: 18px;
    align-items: center;
}

.profile-tabs {
    background: #fff;
    border-radius: 8px;
    margin-top: 16px;
    padding: 0 24px;
    display: flex;
    gap: 24px;
    border-bottom: 1px solid #eee;
}

.profile-tabs .nav-link {
    color: #555;
    font-weight: 500;
    padding: 14px 0;
    border: none;
    background: none;
}

.profile-tabs .nav-link.active {
    color: #fff;
    background: #6366f1;
    border-radius: 6px 6px 0 0;
    padding: 14px 24px;
}

.profile-content {
    margin-top: 24px;
}

.profile-content .row {
    margin-left: 0;
    margin-right: 0;
}

.profile-section-title {
    font-size: 13px;
    color: #888;
    font-weight: 700;
    margin-bottom: 16px;
    letter-spacing: 1px;
}

.profile-list {
    list-style: none;
    padding: 0;
    margin: 0 0 24px 0;
}

.profile-list li {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    font-size: 15px;
    color: #444;
}

.profile-list li i {
    font-size: 18px;
    margin-right: 10px;
    color: #6366f1;
    min-width: 22px;
    text-align: center;
}

.btn-primary {
    background-color: #6366f1;
    border-color: #6366f1;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background-color: #4f46e5 !important;
    border-color: #4f46e5 !important;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
    color: white !important;
}
</style>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <?php
                include __DIR__ . '/../common/menu-sidebar.php';
            ?>

            <!-- Layout container -->
            <div class="layout-page">
            <?php
                include __DIR__ . '/../common/navbar.php';
            ?>
                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <!-- Header -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="mb-1">Chi tiết phòng</h4>
                            </div>
                        </div>

                        <!-- Profile Content -->
                        <div class="container-xxl grow container-p-y">
                            <div class="profile-cover"></div>
                            <div class="profile-header">
                                <?php if (!empty($room['image_url'])): ?>
                                <img src="../../<?php echo htmlspecialchars($room['image_url']); ?>" alt="Room Image"
                                    class="profile-avatar">
                                <?php else: ?>
                                <img src="../../assets/img/default-room.jpg" alt="Default Room" class="profile-avatar"
                                    onerror="this.src='https://via.placeholder.com/150?text=No+Image'">
                                <?php endif; ?>

                                <div class="profile-info">
                                    <h3><?php echo htmlspecialchars($room['room_name']); ?></h3>
                                    <div class="meta">
                                        <span><i class="bx bx-barcode"></i> Mã phòng:
                                            <?php echo htmlspecialchars($room['room_code']); ?></span>
                                        <span><i class="bx bx-calendar"></i> Tạo lúc
                                            <?php echo date('d/m/Y', strtotime($room['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="profile-tabs mt-3">
                                <div class="profile-content ">
                                    <div class="profile-section-title">THÔNG TIN PHÒNG</div>
                                    <ul class="profile-list">
                                        <li><i class="bx bx-barcode"></i> <strong>Mã phòng:</strong>&nbsp;
                                            <?php echo htmlspecialchars($room['room_code']); ?></li>
                                        <li><i class="bx bx-door-open"></i> <strong>Tên phòng:</strong>&nbsp;
                                            <?php echo htmlspecialchars($room['room_name']); ?></li>
                                        <li><i class="bx bx-category"></i> <strong>Loại phòng:</strong>&nbsp;
                                            <?php echo htmlspecialchars($room['type_name'] ?? 'Chưa xác định'); ?></li>
                                        <li><i class="bx bx-building"></i> <strong>Tòa nhà:</strong>&nbsp;
                                            <?php echo htmlspecialchars($room['building'] ?? 'Chưa có thông tin'); ?></li>
                                        <li><i class="bx bx-layer"></i> <strong>Tầng:</strong>&nbsp;
                                            <?php echo $room['floor'] ? htmlspecialchars($room['floor']) : 'Chưa có thông tin'; ?></li>
                                        <li><i class="bx bx-group"></i> <strong>Sức chứa:</strong>&nbsp;
                                            <?php echo $room['capacity'] ? htmlspecialchars($room['capacity']) . ' người' : 'Chưa có thông tin'; ?></li>
                                        <li><i class="bx bx-info-circle"></i> <strong>Trạng thái:</strong>&nbsp;
                                            <span class="badge bg-<?php
                                                echo $room['status'] == 'trong' ? 'success' :
                                                    ($room['status'] == 'dang_su_dung' ? 'warning' : 'danger');
                                            ?>"><?php
                                                if ($room['status'] == 'trong') echo 'Phòng trống';
                                                else echo $status_display;
                                            ?></span>
                                        </li>
                                        <li><i class="bx bx-check-circle"></i> <strong>Hoạt động:</strong>&nbsp;
                                            <span class="badge bg-<?php echo $room['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $room['is_active'] ? 'Có' : 'Không'; ?>
                                            </span>
                                        </li>
                                        <li><i class="bx bx-calendar"></i> <strong>Ngày tạo:</strong>&nbsp;
                                            <?php echo date('d/m/Y H:i', strtotime($room['created_at'])); ?></li>
                                        <?php if (!empty($room['updated_at'])): ?>
                                        <li><i class="bx bx-calendar-edit"></i> <strong>Cập nhật lần cuối:</strong>&nbsp;
                                            <?php echo date('d/m/Y H:i', strtotime($room['updated_at'])); ?></li>
                                        <?php endif; ?>
                                    </ul>
                                    <div class="profile-section-title">THIẾT BỊ & TIỆN NGHI</div>
                                    <ul class="profile-list">
                                        <li><i class="bx bx-devices"></i> <strong>Danh sách thiết bị:</strong>&nbsp;
                                            <?php echo htmlspecialchars($facilities_display); ?></li>
                                    </ul>

                                    <hr>
                                    <div class="mt-4 pb-4">
                                        <?php if ($room['is_active'] == 1): ?>
                                            <?php if ($room['status'] == 'bao_tri'): ?>
                                                <button class="btn btn-primary" onclick="showMaintenanceAlert('<?= htmlspecialchars($room['room_code']) ?>')">
                                                    <i class="bx bx-calendar-plus me-1"></i> Đặt phòng này
                                                </button>
                                            <?php else: ?>
                                                <a href="../booking/booking-room.php?room_id=<?= $room['id'] ?>" class="btn btn-primary">
                                                    <i class="bx bx-calendar-plus me-1"></i> Đặt phòng này
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <a href="list-room.php" class="btn btn-outline-secondary ms-2">
                                            <i class="bx bx-arrow-back me-1"></i> Quay lại danh sách
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Footer -->
                        <?php
              include __DIR__ . '/../common/footer.php';
            ?>
                        <!-- / Footer -->
                    </div>
                    <!-- Content wrapper -->
                </div>
                <!-- / Layout container -->
            </div>

            <!-- Overlay -->
        </div>
        <!-- / Layout wrapper -->

        <!-- Core JS -->
        <!-- build:js assets/vendor/js/core.js -->
        <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
        <script src="../../assets/vendor/libs/popper/popper.js"></script>
        <script src="../../assets/vendor/js/bootstrap.js"></script>
        <script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
        <script src="../../assets/vendor/js/menu.js"></script>
        <!-- endbuild -->

        <!-- Main JS -->
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            function showMaintenanceAlert(roomCode) {
                Swal.fire({
                    icon: 'error',
                    title: 'Không thể đặt phòng',
                    text: 'Phòng ' + roomCode + ' đang trong quá trình bảo trì. Vui lòng chọn phòng khác hoặc quay lại sau.',
                    confirmButtonColor: '#3085d6'
                });
            }
        </script>

        <script>
        $(document).ready(function() {
            $('#userTable').DataTable({
                dom: 'Bfrtip',
                buttons: [{
                    extend: 'pdf',
                    className: 'btn btn-outline-secondary'
                }],
                columnDefs: [{
                    orderable: false,
                    targets: [7]
                }]
            });
        });
        </script>
</body>

</html>