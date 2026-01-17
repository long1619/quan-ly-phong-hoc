<?php
    session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../auth/login.php');
        exit;
    }
    include __DIR__ . '/../common/header.php';
    require_once __DIR__ . '/../../config/connect.php';
    require_once __DIR__ . '/../../helpers/helpers.php';

    $userRole = $_SESSION['user_role'] ?? '';

    // Kiểm tra quyền xem
    if (!checkPermission($conn, $userRole, 'view_room')) {
        echo "<script>alert('Bạn không có quyền xem danh sách phòng!'); window.location.href='../dashboard/index.php';</script>";
        exit;
    }

    require_once __DIR__ . '/../common/alert.php';
    require_once __DIR__ . '/../common/paginate.php';

    // Xử lý phân trang
    $limit = 10; // Hiển thị 10 phòng mỗi trang
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    // Đếm tổng số phòng
    $countSql = "SELECT COUNT(*) as total FROM rooms";
    $countResult = $conn->query($countSql);
    $totalRooms = $countResult->fetch_assoc()['total'];

    // Tính toán phân trang
    $pagination = paginate($totalRooms, $page, $limit);
    $offset = $pagination['offset'];
    $totalPages = $pagination['total_pages'];
    $currentPage = $pagination['current_page'];

    // Lấy danh sách phòng theo phân trang
    $sql = "SELECT * FROM rooms ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $result = $conn->query($sql);

    // Thông báo thành công (nếu có)
    $success = $_SESSION['success'] ?? null;
    unset($_SESSION['success']);

?>
<style>
.card-action-top {
    position: absolute;
    top: 18px;
    right: 18px;
    display: flex;
    gap: 8px;
    z-index: 2;
}
.card-action-top .btn-action {
    background: #fff;
    border: none;
    border-radius: 50%;
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    transition: background 0.2s;
    color: #667eea;
    font-size: 18px;
    padding-left: 50%;
}
.card-action-top .btn-action:hover {
    background: #e7f1ff;
    color: #333;
}
.classroom-card {
    position: relative;
    border: none;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.classroom-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    transform: translateY(-4px);
}

.classroom-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 20px;
    text-align: center;
    color: white;
}

.classroom-header.a201 {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.classroom-header h2 {
    font-size: 48px;
    font-weight: bold;
    margin: 0;
    color: #fff;
}

.classroom-info {
    padding: 20px;
}

.classroom-info h5 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
}

.info-row {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    font-size: 14px;
    color: #666;
}

.info-row i {
    margin-right: 8px;
    color: #667eea;
}

.badge-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 12px;
}

.badge-available {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.badge-full {
    background-color: #fff3e0;
    color: #ef6c00;
}

.badge-maintenance {
    background-color: #ffebee;
    color: #c62828;
}

.tags {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.tag {
    display: inline-block;
    padding: 4px 10px;
    background-color: #f5f5f5;
    border-radius: 4px;
    font-size: 12px;
    color: #666;
}

.action-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding-top: 12px;
    border-top: 1px solid #eee;
}

.action-buttons button {
    font-size: 13px;
    padding: 8px 12px;
    border-radius: 4px;
}

.btn-schedule {
    background-color: white;
    color: #333;
    border: 1px solid #ddd;
}

.btn-schedule:hover {
    background-color: #f5f5f5;
}

.btn-book {
    background-color: #667eea;
    color: white !important;
    border: none;
    transition: all 0.2s ease;
}

.btn-book:hover {
    background-color: #4a54e1;
    color: white !important;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.header-left h4 {
    font-weight: 700;
    margin: 0;
}

.search-filter {
    display: flex;
    gap: 15px;
    align-items: center;
}

.search-box {
    flex: 1;
    min-width: 300px;
}

.search-box input {
    border-radius: 6px;
    border: 1px solid #ddd;
}

.btn-add {
    background-color: #667eea;
    color: white !important;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-add:hover {
    background-color: #4a54e1;
    color: white !important;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}
</style>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <?php include __DIR__ . '/../common/menu-sidebar.php'; ?>

            <!-- Layout container -->
            <div class="layout-page">
                <?php include __DIR__ . '/../common/navbar.php'; ?>

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->

                    <div class="container-xxl grow container-p-y">
                        <!-- Header -->
                        <div class="header-section">
                            <div class="header-left">
                                <h4>Danh sách phòng (<?= $totalRooms ?> phòng)</h4>
                            </div>
                            <div class="search-filter">
                                <div class="search-box">
                                    <div class="input-group">
                                        <i class="bx bx-search input-group-text"></i>
                                        <input type="text" id="roomSearchInput" class="form-control" placeholder="Tìm kiếm phòng theo mã, tên hoặc tòa nhà..." />
                                    </div>
                                </div>
                                <?php if (checkPermission($conn, $userRole, 'add_room')): ?>
                                <a href="add-room.php">
                                    <button class="btn btn-add">+ Thêm phòng</button>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Classroom Grid -->
                        <div class="row row-cols-1 row-cols-md-2 g-4 mb-5" id="roomGrid">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($room = $result->fetch_assoc()): ?>
                                <div class="col room-card-item">
                                    <div class="card classroom-card">
                                        <!-- Action buttons top right -->
                                        <div class="card-action-top">
                                            <div class="dropdown">
                                                <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                                    <li>
                                                        <a class="dropdown-item" href="detail-room.php?id=<?= $room['id'] ?>">
                                                            <i class="bx bx-info-circle me-2"></i> Chi tiết
                                                        </a>
                                                    </li>
                                                    <?php if (checkPermission($conn, $userRole, 'edit_room')): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="edit-room.php?id=<?= $room['id'] ?>">
                                                            <i class="bx bx-edit me-2"></i> Sửa
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>

                                                    <?php if (checkPermission($conn, $userRole, 'delete_room')): ?>
                                                    <li>
                                                        <a href="#" class="dropdown-item text-danger btn-delete-room" data-id="<?= $room['id'] ?>">
                                                            <i class="bx bx-trash me-2"></i> Xóa
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="classroom-header<?= isset($room['room_code']) && $room['room_code'] == 'A201' ? ' a201' : '' ?>">
                                            <h2><?= htmlspecialchars($room['room_code'] ?? '') ?></h2>
                                        </div>
                                        <div class="classroom-info">
                                            <h5><?= htmlspecialchars($room['room_name'] ?? '') ?></h5>
                                            <?php
                                                $statusClass = 'badge-available';
                                                $statusText = 'Trống';

                                                if ($room['status'] == 'dang_su_dung') {
                                                    $statusClass = 'badge-full';
                                                    $statusText = 'Đang sử dụng';
                                                } elseif ($room['status'] == 'bao_tri') {
                                                    $statusClass = 'badge-maintenance';
                                                    $statusText = 'Đang bảo trì';
                                                } else {
                                                    $statusClass = 'badge-available';
                                                    $statusText = 'Phòng trống';
                                                }
                                            ?>
                                            <div class="badge-status <?= $statusClass ?>">
                                                <?= $statusText ?>
                                            </div>
                                            <!-- Building + Floor -->
                                            <div class="info-row">
                                                <i class="bx bx-map"></i>
                                                <span>
                                                    <?= htmlspecialchars($room['building'] ?? '') ?>
                                                    <?= isset($room['floor']) && $room['floor'] !== '' ? ', Tầng ' . htmlspecialchars($room['floor']) : '' ?>
                                                </span>
                                            </div>
                                            <!-- Capacity -->
                                            <div class="info-row">
                                                <i class="bx bx-user"></i>
                                                <span>Sức chứa: <?= (int)($room['capacity'] ?? 0) ?> người</span>
                                            </div>
                                            <!-- Facilities -->
                                            <div class="tags">
                                                <?php
                                                $facilities = [];
                                                if (!empty($room['facilities'])) {
                                                    $decoded = json_decode($room['facilities'], true);
                                                    if (is_array($decoded)) {
                                                        $facilities = $decoded;
                                                    }
                                                }
                                                foreach ($facilities as $facility): ?>
                                                    <span class="tag"><?= htmlspecialchars($facility) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="action-buttons">
                                                <a class="btn btn-schedule" href="../booking/calendar-room.php?id=<?= $room['id'] ?>">Xem lịch phòng</a>
                                                <?php if ($room['is_active'] == 1 && checkPermission($conn, $userRole, 'create_booking')): ?>
                                                    <?php if ($room['status'] == 'bao_tri'): ?>
                                                        <button class="btn btn-book" onclick="showMaintenanceAlert('<?= htmlspecialchars($room['room_code']) ?>')">Đặt phòng</button>
                                                    <?php else: ?>
                                                        <a class="btn btn-book" href="../booking/booking-room.php?room_id=<?= $room['id'] ?>">Đặt phòng</a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col">
                                <div class="alert alert-info">Không có phòng nào.</div>
                            </div>
                        <?php endif; ?>
                        </div>
                        <!--/ Classroom Grid -->

                        <!-- Pagination -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <?= renderPagination($totalPages, $currentPage, $_SERVER['REQUEST_URI']) ?>
                            </div>
                        </div>
                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    <?php include __DIR__ . '/../common/footer.php'; ?>
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
    <!-- <script src="../../assets/vendor/libs/popper/popper.js"></script> -->
    <script src="../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../../assets/vendor/js/menu.js"></script>
    <!-- Main JS -->
    <script src="../../assets/js/main.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Hiển thị thông báo thành công sau khi thêm mới, edit -->
    <?php showSuccessAlert($success); ?>
    <script>
        $(document).ready(function() {
            // Xử lý xác nhận xóa
            setupDeleteConfirmation(
                '.btn-delete-room',
                'delete-room.php?id={id}',
                'id',
                'Bạn có chắc chắn muốn xóa phòng này?',
                'Thao tác này không thể hoàn tác!'
            );

            // Chức năng tìm kiếm phòng
            $('#roomSearchInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                var visibleCount = 0;

                $("#roomGrid .room-card-item").each(function() {
                    var text = $(this).text().toLowerCase();
                    var isMatch = text.indexOf(value) > -1;
                    $(this).toggle(isMatch);
                    if (isMatch) visibleCount++;
                });

                // Hiển thị thông báo nếu không tìm thấy kết quả
                if (visibleCount === 0 && $("#roomGrid .room-card-item").length > 0) {
                    if ($('#noResultsMsg').length === 0) {
                        $('#roomGrid').append('<div id="noResultsMsg" class="col-12"><div class="alert alert-warning text-center">Không tìm thấy phòng nào phù hợp với từ khóa "' + $(this).val() + '"</div></div>');
                    } else {
                        $('#noResultsMsg .alert').text('Không tìm thấy phòng nào phù hợp với từ khóa "' + $(this).val() + '"');
                        $('#noResultsMsg').show();
                    }
                } else {
                    $('#noResultsMsg').hide();
                }
            });
        });

        function showMaintenanceAlert(roomCode) {
            Swal.fire({
                icon: 'error',
                title: 'Không thể đặt phòng',
                text: 'Phòng ' + roomCode + ' đang trong quá trình bảo trì. Vui lòng chọn phòng khác hoặc quay lại sau.',
                confirmButtonColor: '#3085d6'
            });
        }
    </script>
</body>
</html>