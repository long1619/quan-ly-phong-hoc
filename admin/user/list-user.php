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
    if (!checkPermission($conn, $userRole, 'view_user')) {
        echo "<script>alert('Bạn không có quyền xem danh sách người dùng!'); window.location.href='../dashboard/index.php';</script>";
        exit;
    }

    $idLogin = $_SESSION['user_id'];
    $success = $_SESSION['success'] ?? null;
    unset($_SESSION['success']);
    require_once __DIR__ . '/../common/alert.php';

    // Lấy danh sách user từ database
    $sql = "SELECT * FROM users WHERE id != $idLogin ORDER BY created_at DESC";
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
                                <h4 class="mb-1">Danh sách người dùng</h4>
                            </div>
                        </div>
                        <!-- DataTable Card -->
                        <div class="card mb-4">
                            <!-- <div class="card-header d-flex justify-content-end align-items-center">
                                <a href="add-user.php" class="btn btn-primary" id="addNewRecord"><i
                                        class="bx bx-plus"></i>Thêm mới người dùng</a>
                            </div> -->
                            <div class="card-body">
                                <!-- filter -->
                                <div class="group-filter-button border-bottom">
                                    <div class="d-flex justify-content-between align-items-center row">
                                        <div class="col-md-3 user_role">
                                            <select id="UserRole" class="form-select text-capitalize">
                                                <option value="">Chọn vai trò</option>
                                                <option value="Giảng Viên">Giảng viên</option>
                                                <option value="Sinh Viên">Sinh viên</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-center">
                                            <!-- Removed UserStatus select -->
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" id="customSearch" class="form-control"
                                                placeholder="Tìm kiếm...">
                                        </div>
                                        <div class="col-md-3">
                                            <div class="d-flex justify-content-end align-items-center">
                                                <?php if (checkPermission($conn, $userRole, 'add_user')): ?>
                                                <a href="add-user.php" class="btn btn-primary" id="addNewRecord" style="background: var(--primary-gradient); border: none; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                                                    <i class="bx bx-plus"></i>Thêm mới người dùng
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- end filter -->

                                <table id="userTable" class="table table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Avatar</th>
                                            <th>Họ tên</th>
                                            <th>Email</th>
                                            <th>Vai trò</th>
                                            <th>Khoa/Phòng</th>
                                            <th>Ngày tạo</th>
                                            <th>Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($row['avatar'])): ?>
                                                <img src="../../assets/<?php echo htmlspecialchars($row['avatar']); ?>"
                                                    alt="avatar" class="rounded-circle" width="36" height="36">
                                                <?php else: ?>
                                                <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="avatar"
                                                    class="rounded-circle" width="36" height="36">
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo ($row['role'] == 'giang_vien' ? 'Giảng Viên' : 'Sinh Viên'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <a href="profile.php?id=<?php echo $row['id']; ?>"
                                                    class="btn btn-sm btn-icon btn-outline-info" title="Xem chi tiết">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <?php if (checkPermission($conn, $userRole, 'edit_user')): ?>
                                                <a href="edit-user.php?id=<?php echo $row['id']; ?>"
                                                    class="btn btn-sm btn-icon btn-outline-secondary" title="Sửa">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <?php endif; ?>

                                                <?php if (checkPermission($conn, $userRole, 'delete_user')): ?>
                                                <a href="#"
                                                    class="btn btn-sm btn-icon btn-outline-danger btn-delete-user"
                                                    data-id="<?php echo $row['id']; ?>" title="Xóa">
                                                    <i class="bx bx-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Không có người dùng nào.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- /DataTable Card -->
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

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
    <!-- <script src="../../assets/vendor/libs/popper/popper.js"></script> -->
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
    <!-- Xuất file -->
    <!-- <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script> -->
    <!-- Hỗ trợ xuất file Excel -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script> -->

    <script>
    $(document).ready(function() {
        var table = $('#userTable').DataTable({
            dom: 'Bfrtip',
            pageLength: 10,
            // buttons: [
            //     { extend: 'copy', className: 'btn btn-outline-secondary' },
            //     { extend: 'excel', className: 'btn btn-outline-secondary' },
            //     { extend: 'csv', className: 'btn btn-outline-secondary' },
            //     { extend: 'pdf', className: 'btn btn-outline-secondary' },
            //     { extend: 'print', className: 'btn btn-outline-secondary' }
            // ],
            columnDefs: [{
                orderable: false,
                targets: [6]
            }],
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

        // Filter theo vai trò
        $('#UserRole').on('change', function() {
            var val = $(this).val();
            table.column(3).search(val, true, false).draw();
        });

        // Tìm kiếm tùy chỉnh
        $('#customSearch').on('keyup', function() {
            table.search(this.value).draw();
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Hiển thị thông báo thành công  -->
    <?php showSuccessAlert($success); ?>
    <script>
        setupDeleteConfirmation(
            '.btn-delete-user',
            'delete-user.php?id={id}',
            'id',
            'Bạn có chắc chắn?',
            'Bạn sẽ không thể khôi phục lại tài khoản này!'
        );
    </script>
</body>

</html>