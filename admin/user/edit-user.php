<?php
    session_start();

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../auth/login.php');
        exit;
    }
    include __DIR__ . '/../common/header.php';
    require_once __DIR__ . '/../../helpers/helpers.php';
    require_once __DIR__ . '/../../config/connect.php';

    $userRole = $_SESSION['user_role'] ?? '';

    // Kiểm tra quyền sửa người dùng
    if (!checkPermission($conn, $userRole, 'edit_user')) {
        echo "<script>alert('Bạn không có quyền chỉnh sửa người dùng!'); window.location.href='../dashboard/index.php';</script>";
        exit;
    }

    // Lấy lỗi và dữ liệu cũ nếu có
    $errors = $_SESSION['errors'] ?? [];
    $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);

    // Lấy id user
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        header('Location: list-user.php');
        exit;
    }
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) {
        header('Location: list-user.php');
        exit;
    }

    // Nếu có dữ liệu cũ (sau khi validate lỗi), dùng dữ liệu cũ thay cho dữ liệu từ DB
    $display = function($field) use ($old, $user) {
        return isset($old[$field]) ? htmlspecialchars($old[$field]) : htmlspecialchars($user[$field]);
    };

    $success = $_SESSION['success'] ?? null;
    unset($_SESSION['success']);
    require_once __DIR__ . '/../common/alert.php';
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
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <!-- Header -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="mb-1">Chỉnh sửa người dùng</h4>
                            </div>
                        </div>
                        <!-- Hiển thị lỗi validate -->
                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <!-- DataTable Card -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <form id="formAccountSettings" method="POST" action="process-edit-user.php"
                                    enctype="multipart/form-data">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <div class="row">
                                        <div class="mb-3 col-md-6">
                                            <label for="username" class="form-label">Tên đăng nhập</label>
                                            <input class="form-control" type="text" id="username" name="username"
                                                value="<?= $display('username') ?>" />
                                        </div>
                                        <div class="mb-3 col-md-6 form-password-toggle">
                                            <div class="d-flex justify-content-between">
                                                <label class="form-label" for="password">Mật Khẩu</label>
                                            </div>
                                            <div class="input-group input-group-merge">
                                                <input type="password" id="password" class="form-control"
                                                    name="password" placeholder="••••••••••••"
                                                    aria-describedby="password" />
                                                <span class="input-group-text cursor-pointer"><i
                                                        class="bx bx-hide"></i></span>
                                            </div>
                                            <small class="text-muted">Để trống nếu không đổi mật khẩu</small>
                                        </div>

                                        <div class="mb-3 col-md-6">
                                            <label for="avatar" class="form-label">Ảnh đại diện</label>
                                            <input class="form-control" type="file" id="avatar" name="avatar"
                                                accept="image/*" onchange="previewAvatar(event)" />
                                            <div style="margin-top:10px;">
                                                <img id="avatar-preview"
                                                    src="<?php echo !empty($user['avatar']) ? '../../assets/' . htmlspecialchars($user['avatar']) : ''; ?>"
                                                    alt="Preview"
                                                    style="max-width:100%; max-height:200px; <?php echo !empty($user['avatar']) ? '' : 'display:none;'; ?> border-radius:8px;">
                                            </div>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="full_name" class="form-label">Họ và tên</label>
                                            <input class="form-control" type="text" id="full_name" name="full_name"
                                                value="<?= $display('full_name') ?>" />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input class="form-control" type="email" id="email" name="email"
                                                value="<?= $display('email') ?>" />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="phone" class="form-label">Số điện thoại</label>
                                            <input class="form-control" type="text" id="phone" name="phone"
                                                value="<?= $display('phone') ?>" />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="role" class="form-label">Vai trò</label>

                                            <select class="form-select" id="role" name="role"
                                                onchange="toggleCodeFields()"
                                                <?= (isset($user['role']) && $user['role'] === 'admin') ? 'disabled' : '' ?>>
                                                <option value="">Chọn vai trò</option>

                                                <option value="sinh_vien"
                                                    <?= ($display('role') == 'sinh_vien') ? 'selected' : '' ?>>
                                                    Sinh viên
                                                </option>

                                                <option value="giang_vien"
                                                    <?= ($display('role') == 'giang_vien') ? 'selected' : '' ?>>
                                                    Giảng viên
                                                </option>

                                                <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                                                <option value="admin"
                                                    <?= ($display('role') === 'admin') ? 'selected' : '' ?>>
                                                    Admin
                                                </option>
                                                <?php endif; ?>
                                            </select>

                                            <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                                            <input type="hidden" name="role" value="admin">
                                            <?php endif; ?>
                                        </div>


                                        <div class="mb-3 col-md-6" id="student_code_field"
                                            style="display:<?= ($display('role')=='sinh_vien')?'block':'none'; ?>;">
                                            <label for="student_code" class="form-label">Mã sinh viên</label>
                                            <input class="form-control" type="text" id="student_code"
                                                name="student_code" value="<?= $display('student_code') ?>" />
                                        </div>
                                        <div class="mb-3 col-md-6" id="employee_code_field"
                                            style="display:<?= ($display('role')=='giang_vien')?'block':'none'; ?>;">
                                            <label for="employee_code" class="form-label">Mã giảng viên</label>
                                            <input class="form-control" type="text" id="employee_code"
                                                name="employee_code" value="<?= $display('employee_code') ?>" />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="department" class="form-label">Phòng ban</label>
                                            <input class="form-control" type="text" id="department" name="department"
                                                value="<?= $display('department') ?>" />
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-primary me-2" style="background: var(--primary-gradient); border: none; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">Cập nhật</button>
                                        <button type="reset" class="btn btn-outline-secondary">Hủy</button>
                                    </div>
                                </form>
                                <script>
                                function toggleCodeFields() {
                                    var role = document.getElementById('role').value;
                                    document.getElementById('student_code_field').style.display = (role ===
                                        'sinh_vien') ? 'block' : 'none';
                                    document.getElementById('employee_code_field').style.display = (role ===
                                        'giang_vien') ? 'block' : 'none';
                                }
                                window.onload = function() {
                                    toggleCodeFields();
                                };

                                function previewAvatar(event) {
                                    const input = event.target;
                                    const preview = document.getElementById('avatar-preview');
                                    if (input.files && input.files[0]) {
                                        const reader = new FileReader();
                                        reader.onload = function(e) {
                                            preview.src = e.target.result;
                                            preview.style.display = 'block';
                                        }
                                        reader.readAsDataURL(input.files[0]);
                                    }
                                }
                                </script>
                            </div>
                        </div>
                        <!-- /DataTable Card -->
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
    <!-- build:js assets/vendor/js/core.js -->
    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
    <!-- <script src="../../assets/vendor/libs/popper/popper.js"></script> -->
    <script src="../../assets/vendor/js/bootstrap.js"></script>
    <!-- <script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script> -->
    <script src="../../assets/vendor/js/menu.js"></script>
    <!-- endbuild -->
    <!-- Main JS -->
    <script src="../../assets/js/main.js"></script>

    <?php showSuccessAlert($success); ?>

</body>

</html>