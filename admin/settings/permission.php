<?php
    session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../auth/login.php');
        exit;
    }
    // Chỉ admin mới được vào trang này
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin') {
        echo "<script>alert('Bạn không có quyền truy cập trang này!'); window.location.href='../dashboard/index.php';</script>";
        exit;
    }

    include __DIR__ . '/../common/header.php';
    require_once __DIR__ . '/../../config/connect.php';

    $roles = [
        'giang_vien' => 'Giảng Viên',
        'sinh_vien' => 'Sinh Viên'
    ];

    $modules = [
        'room_type' => [
            'name' => 'Quản lý Danh mục Phòng',
            'permissions' => [
                'view_room_type' => 'Xem danh sách loại phòng',
                'add_room_type' => 'Thêm loại phòng mới',
                'edit_room_type' => 'Chỉnh sửa loại phòng',
                'delete_room_type' => 'Xóa loại phòng'
            ]
        ],
        'room' => [
            'name' => 'Quản lý Phòng',
            'permissions' => [
                'view_room' => 'Xem danh sách phòng',
                'add_room' => 'Thêm phòng mới',
                'edit_room' => 'Chỉnh sửa phòng',
                'delete_room' => 'Xóa phòng'
            ]
        ],
        'booking' => [
            'name' => 'Đặt Lịch & Phê Duyệt',
            'permissions' => [
                'create_booking' => 'Tạo yêu cầu đặt phòng',
                'cancel_booking' => 'Hủy đơn đặt',
                'approve_booking' => 'Phê duyệt đơn (Admin)',
                'view_history' => 'Xem lịch sử'
            ]
        ],
        'user' => [
            'name' => 'Người Dùng',
            'permissions' => [
                'view_user' => 'Xem danh sách user',
                'add_user' => 'Thêm user',
                'edit_user' => 'Sửa user',
                'delete_user' => 'Xóa user'
            ]
        ],
        'system' => [
            'name' => 'Hệ Thống',
            'permissions' => [
                'view_news' => 'Xem danh sách tin tức',
                'add_news' => 'Thêm tin tức mới',
                'edit_news' => 'Chỉnh sửa tin tức',
                'delete_news' => 'Xóa tin tức',
                'use_ai' => 'Sử dụng AI Assistant'
            ]
        ]
    ];

    // Load existing permissions from DB
    $dbPermissions = [];
    $sql_fetch = "SELECT role, permission_key, active FROM permissions";
    $res_fetch = $conn->query($sql_fetch);
    if ($res_fetch) {
        while ($row = $res_fetch->fetch_assoc()) {
            $dbPermissions[$row['role']][$row['permission_key']] = $row['active'];
        }
    }
?>

<!-- Custom CSS for Switches -->
<style>
    .permission-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.2s;
        margin-bottom: 2rem;
    }
    .permission-card:hover {
        transform: translateY(-2px);
    }
    .permission-header {
        background: linear-gradient(135deg, #696cff 0%, #8592fa 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px 12px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .permission-title {
        font-weight: 700;
        font-size: 1.1rem;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: white !important;
    }
    .role-col {
        border-left: 1px solid #eee;
        text-align: center;
    }
    .table-permission th {
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1px;
        color: #a1acb8;
        padding: 1rem;
    }
    .table-permission td {
        vertical-align: middle;
        padding: 1rem;
    }
    .perm-name {
        font-weight: 600;
        color: #566a7f;
    }

    /* IOS Toggle Switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #d1d5db;
        transition: .4s;
        border-radius: 24px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    input:checked + .slider {
        background-color: #696cff;
    }
    input:focus + .slider {
        box-shadow: 0 0 1px #696cff;
    }
    input:checked + .slider:before {
        transform: translateX(20px);
    }

    /* Save Bar */
    .save-bar {
        position: fixed;
        bottom: 20px;
        right: 30px;
        z-index: 999;
        display: none; /* Hidden by default */
        animation: slideUp 0.3s ease-out forwards;
    }
    @keyframes slideUp {
        from { transform: translateY(100px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .save-btn {
        padding: 12px 28px;
        font-weight: 700;
        box-shadow: 0 4px 20px rgba(105, 108, 255, 0.4);
    }
</style>

<body>
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../common/menu-sidebar.php'; ?>

        <div class="layout-page">
            <!-- Navbar -->
            <?php include __DIR__ . '/../common/navbar.php'; ?>

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="fw-bold py-1 mb-0"><span class="text-muted fw-light">Hệ thống /</span> Phân quyền</h4>
                            <small class="text-muted">Cấu hình quyền hạn cho Giảng viên và Sinh viên</small>
                        </div>
                    </div>

                    <form id="permissionForm" method="POST">

                        <!-- Iterate Modules -->
                        <?php foreach ($modules as $modKey => $module): ?>
                        <div class="card permission-card">
                            <div class="permission-header">
                                <h5 class="permission-title">
                                    <i class='bx bx-layer'></i> <?php echo $module['name']; ?>
                                </h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-permission mb-0">
                                    <thead>
                                        <tr>
                                            <th width="40%">Chức năng</th>
                                            <?php foreach ($roles as $roleKey => $roleName): ?>
                                                <th width="30%" class="text-center role-col">
                                                    <?php echo $roleName; ?>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($module['permissions'] as $permKey => $permName): ?>
                                        <tr>
                                            <td class="perm-name"><?php echo $permName; ?></td>

                                            <?php foreach ($roles as $roleKey => $roleName): ?>
                                            <td class="text-center role-col">
                                                <label class="switch">
                                                    <?php
                                                        $isChecked = (isset($dbPermissions[$roleKey][$permKey]) && $dbPermissions[$roleKey][$permKey] == 1) ? 'checked' : '';
                                                    ?>
                                                    <input type="checkbox" name="perm[<?php echo $roleKey; ?>][<?php echo $permKey; ?>]" <?php echo $isChecked; ?> class="perm-checkbox">
                                                    <span class="slider"></span>
                                                </label>
                                            </td>
                                            <?php endforeach; ?>

                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Floating Save Button -->
                        <div class="save-bar" id="saveBar">
                            <button type="submit" class="btn btn-primary rounded-pill save-btn">
                                <i class='bx bx-save me-1'></i> Lưu thay đổi
                            </button>
                        </div>

                    </form>

                </div>
                <?php include __DIR__ . '/../common/footer.php'; ?>
            </div>
        </div>
    </div>
</div>

<!-- Core JS -->
<script src="../../assets/vendor/libs/jquery/jquery.js"></script>
<script src="../../assets/vendor/js/bootstrap.js"></script>
<script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="../../assets/vendor/js/menu.js"></script>
<script src="../../assets/js/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // Show save button when any checkbox changes
        $('.perm-checkbox').on('change', function() {
            $('#saveBar').fadeIn();
        });

        // Form Submit via AJAX
        $('#permissionForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'save-permissions.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công!',
                            text: 'Cấu hình phân quyền đã được cập nhật.',
                            confirmButtonText: 'Đóng',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                        });
                        $('#saveBar').fadeOut();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: response.message || 'Không thể lưu cấu hình.',
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: 'Đã xảy ra lỗi kết nối.',
                    });
                }
            });
        });
    });
</script>

</body>
</html>
