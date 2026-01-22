<?php
    session_start();

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../auth/login.php');
        exit;
    }
    include __DIR__ . '/../common/header.php';
    require_once __DIR__ . '/../../helpers/helpers.php';
    require_once __DIR__ . '/../../config/connect.php';

    // Lấy ID người dùng từ session - Đảm bảo họ chỉ có thể sửa thông tin của chính mình
    $id = $_SESSION['user_id'] ? intval($_SESSION['user_id']) : 0;

    if ($id <= 0) {
        header('Location: ../dashboard/index.php');
        exit;
    }

    // Lấy lỗi và dữ liệu cũ nếu có
    $errors = $_SESSION['errors'] ?? [];
    $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        header('Location: ../dashboard/index.php');
        exit;
    }

    // Nếu có dữ liệu cũ (sau khi validate lỗi), dùng dữ liệu cũ thay cho dữ liệu từ DB
    $display = function($field) use ($old, $user) {
        return isset($old[$field]) ? htmlspecialchars($old[$field]) : htmlspecialchars($user[$field] ?? '');
    };

    $success = $_SESSION['success'] ?? null;
    unset($_SESSION['success']);
    require_once __DIR__ . '/../common/alert.php';
?>
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }
    .avatar-preview-container {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto 20px;
    }
    #avatar-preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .upload-btn-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
    }
    .upload-btn-wrapper input[type=file] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
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
                                <h4 class="mb-1">Thông tin cá nhân</h4>
                                <p class="text-muted">Quản lý thông tin tài khoản của bạn</p>
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

                        <div class="row">
                            <!-- Cột trái: Avatar và thông tin cơ bản -->
                            <div class="col-md-4">
                                <div class="card mb-4 mt-1">
                                    <div class="card-body text-center">
                                        <div class="avatar-preview-container">
                                            <img id="avatar-preview"
                                                src="<?php echo !empty($user['avatar']) ? '../../assets/' . htmlspecialchars($user['avatar']) : '../../assets/img/avatars/1.png'; ?>"
                                                alt="Avatar">
                                        </div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                                        <p class="text-muted mb-3">
                                            <span class="badge bg-label-primary text-uppercase">
                                                <?php
                                                    $roles = ['admin' => 'Quản trị viên', 'giang_vien' => 'Giảng viên', 'sinh_vien' => 'Sinh viên'];
                                                    echo $roles[$user['role']] ?? $user['role'];
                                                ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Cột phải: Form chỉnh sửa -->
                            <div class="col-md-8">
                                <div class="card mb-4 mt-1">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Chỉnh sửa hồ sơ</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="formProfile" method="POST" action="process-profile-user.php"
                                            enctype="multipart/form-data">
                                            <div class="row">
                                                <div class="mb-3 col-md-6">
                                                    <label for="username" class="form-label">Tên đăng nhập</label>
                                                    <input class="form-control" type="text" id="username" name="username"
                                                        value="<?= $display('username') ?>" readonly />
                                                    <small class="text-muted">Tên đăng nhập không thể thay đổi</small>
                                                </div>
                                                <div class="mb-3 col-md-6 form-password-toggle">
                                                    <label class="form-label" for="password">Mật khẩu mới</label>
                                                    <div class="input-group input-group-merge">
                                                        <input type="password" id="password" class="form-control"
                                                            name="password" placeholder="••••••••••••" />
                                                        <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                                    </div>
                                                    <small class="text-muted">Để trống nếu không muốn đổi mật khẩu</small>
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
                                                    <label for="department" class="form-label">Phòng ban</label>
                                                    <input class="form-control" type="text" id="department" name="department"
                                                        value="<?= $display('department') ?>" />
                                                </div>

                                                <?php if($user['role'] == 'sinh_vien'): ?>
                                                <div class="mb-3 col-md-6">
                                                    <label for="student_code" class="form-label">Mã sinh viên</label>
                                                    <input class="form-control" type="text" id="student_code"
                                                        name="student_code" value="<?= $display('student_code') ?>" readonly />
                                                </div>
                                                <?php elseif($user['role'] == 'giang_vien'): ?>
                                                <div class="mb-3 col-md-6">
                                                    <label for="employee_code" class="form-label">Mã giảng viên</label>
                                                    <input class="form-control" type="text" id="employee_code"
                                                        name="employee_code" value="<?= $display('employee_code') ?>" readonly />
                                                </div>
                                                <?php endif; ?>

                                                <div class="mb-3 col-md-12">
                                                    <label for="avatar" class="form-label">Thay đổi ảnh đại diện</label>
                                                    <input class="form-control" type="file" id="avatar" name="avatar"
                                                        accept="image/*" onchange="previewAvatar(event)" />
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <button type="submit" class="btn btn-primary me-2" style="background: var(--primary-gradient); border: none; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">Lưu thay đổi</button>
                                                <a href="../dashboard/index.php" class="btn btn-outline-secondary">Quay lại</a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
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
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../assets/vendor/js/menu.js"></script>
    <!-- Main JS -->
    <script src="../../assets/js/main.js"></script>

    <script>
    function previewAvatar(event) {
        const input = event.target;
        const preview = document.getElementById('avatar-preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>

    <?php showSuccessAlert($success); ?>
</body>
</html>
