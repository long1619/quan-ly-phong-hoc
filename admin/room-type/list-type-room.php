<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}
include __DIR__ . '/../common/header.php';
require_once __DIR__ . '/../../config/connect.php';

// Thông báo thành công
$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// Thông báo lỗi
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

require_once __DIR__ . '/../common/alert.php';
require_once __DIR__ . '/../../helpers/helpers.php';

$userRole = $_SESSION['user_role'] ?? '';

// Kiểm tra quyền xem
if (!checkPermission($conn, $userRole, 'view_room_type')) {
    echo "<script>alert('Bạn không có quyền xem loại phòng!'); window.location.href='../dashboard/index.php';</script>";
    exit;
}

// Lấy danh sách loại phòng từ database
$sql = "SELECT * FROM room_types";
$result = $conn->query($sql);
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

.group-filter-button.border-bottom {
    padding-bottom: 1%;
}

.dataTables_filter {
    display: none !important;
}

.color-preview {
    display: inline-block;
    width: 24px;
    height: 24px;
    border-radius: 6px;
    border: 1px solid #ccc;
    vertical-align: middle;
    margin-right: 6px;
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
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <!-- Header -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="mb-1">Danh sách loại phòng</h4>
                            </div>
                        </div>
                        <!-- DataTable Card -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-end align-items-center">
                                <?php if (checkPermission($conn, $userRole, 'add_room_type')): ?>
                                <a href="add-type-room.php" class="btn btn-primary" id="addNewRecord" style="background: var(--primary-gradient); border: none; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                                    <i class="bx bx-plus"></i>Thêm mới loại phòng
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <table id="typeRoomTable" class="table table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Tên loại phòng</th>
                                            <th>Mô tả</th>
                                            <th>Ngày tạo</th>
                                            <th>Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <!-- Tên loại phòng -->
                                            <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                                            <!-- Mô tả -->
                                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                                            <!-- Ngày tạo -->
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                                            </td>
                                            <!-- Hành động -->
                                            <td>
                                                <?php if (checkPermission($conn, $userRole, 'edit_room_type')): ?>
                                                <a href="edit-type-room.php?id=<?php echo $row['id']; ?>"
                                                    class="btn btn-sm btn-outline-info" title="Sửa">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <?php endif; ?>

                                                <?php if (checkPermission($conn, $userRole, 'delete_room_type')): ?>
                                                <a href="#" class="btn btn-sm btn-outline-danger btn-delete-type-room"
                                                    data-id="<?= $row['id'] ?>" title="Xóa">
                                                    <i class="bx bx-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php endif; ?>
                                    </tbody>

                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- / Content -->

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
    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../assets/vendor/libs/popper/popper.js"></script>
    <script src="../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../../assets/vendor/js/menu.js"></script>
    <!-- Main JS -->
    <script src="../../assets/js/main.js"></script>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" />
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        $('#typeRoomTable').DataTable({
            pageLength: 10,
            lengthChange: false,
            language: {
                "zeroRecords": "Không tìm thấy bản ghi phù hợp",
                "infoEmpty": "Không có dữ liệu",
                "info": "Hiển thị _START_ đến _END_ của _TOTAL_ bản ghi",
                "lengthMenu": "Hiển thị _MENU_ bản ghi",
                "search": "Tìm kiếm:",
                "paginate": {
                    "first": "Đầu",
                    "last": "Cuối",
                    "next": "Sau",
                    "previous": "Trước"
                }
            }
        });

        // Xác nhận xóa loại phòng bằng SweetAlert2
        setupDeleteConfirmation(
            '.btn-delete-type-room',
            'delete-type-room.php?id={id}',
            'id',
            'Bạn có chắc muốn xóa loại phòng này?',
            'Thao tác này không thể hoàn tác!'
        );
    });
    </script>

    <!-- Hiển thị thông báo thành công / lỗi sau khi thêm mới, edit, delete -->
    <?php showSuccessAlert($success); ?>
    <?php showErrorAlert($error); ?>
</body>

</html>