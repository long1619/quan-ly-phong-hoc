<?php
    session_start();

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../auth/login.php');
        exit;
    }

    include __DIR__ . '/../common/header.php';
    require_once __DIR__ . '/../../config/connect.php';
    require_once __DIR__ . '/../../helpers/helpers.php';

    // Lấy user id từ URL
    $userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $user = getUserById($conn, $userId);

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
    background: #3b82f6;
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
    color: #3b82f6;
    min-width: 22px;
    text-align: center;
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
                                <h4 class="mb-1">Chi tiết người dùng</h4>
                            </div>
                        </div>

                        <!-- Profile Content -->
                        <div class="container-xxl grow container-p-y">
                            <div class="profile-cover"></div>
                            <div class="profile-header">
                                <!-- <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Avatar"
                                    class="profile-avatar"> -->
                                <?php if (!empty($user['avatar'])): ?>
                                <img src="../../assets/<?php echo htmlspecialchars($user['avatar']); ?>" alt="avatar"
                                    width="150" height="150">
                                <?php else: ?>
                                <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="avatar" width="150"
                                    height="150">
                                <?php endif; ?>

                                <div class="profile-info">
                                    <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                                    <div class="meta">
                                        <span><i class="bx bx-calendar"></i> Tham gia
                                            <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="profile-tabs mt-3">
                                <div class="profile-content ">
                                    <ul class="profile-list">
                                        <li><i class="bx bx-user"></i> <strong>Họ tên:</strong>&nbsp;
                                            <?php echo htmlspecialchars($user['full_name']); ?></li>
                                        <li><i class="bx bx-user-circle"></i> <strong>Tên đăng nhập:</strong>&nbsp;
                                            <?php echo htmlspecialchars($user['username']); ?></li>
                                        <li><i class="bx bx-briefcase"></i> <strong>Vai trò:</strong>&nbsp;
                                            <?php echo htmlspecialchars($user['role']); ?></li>
                                        <li><i class="bx bx-building"></i> <strong>Phòng ban:</strong>&nbsp;
                                            <?php echo htmlspecialchars($user['department']); ?></li>
                                        <?php if (!empty($user['student_code'])): ?>
                                        <li><i class="bx bx-id-card"></i> <strong>Mã sinh viên:</strong>&nbsp;
                                            <?php echo htmlspecialchars($user['student_code']); ?></li>
                                        <?php endif; ?>
                                        <?php if (!empty($user['employee_code'])): ?>
                                        <li><i class="bx bx-id-card"></i> <strong>Mã nhân viên:</strong>&nbsp;
                                            <?php echo htmlspecialchars($user['employee_code']); ?></li>
                                        <?php endif; ?>
                                        <li><i class="bx bx-calendar"></i> <strong>Ngày tham gia:</strong>&nbsp;
                                            <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></li>
                                    </ul>
                                    <div class="profile-section-title">LIÊN HỆ</div>
                                    <ul class="profile-list">
                                        <li><i class="bx bx-phone"></i> <strong>Số điện thoại:</strong>&nbsp;
                                            <?php echo htmlspecialchars($user['phone']); ?></li>
                                        <li><i class="bx bx-envelope"></i> <strong>Email:</strong>&nbsp;
                                            <?php echo htmlspecialchars($user['email']); ?></li>
                                    </ul>
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
        <script src="../../assets/js/main.js"></script>

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