<?php
session_start();
?>

<!DOCTYPE html>
<html lang="vi" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="assets/"
    data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Đăng Nhập</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../../assets/img/logo/school-building.png" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="../../assets/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../../assets/css/demo.css" />

    <!-- Page CSS -->
    <link rel="stylesheet" href="../../assets/vendor/css/pages/page-auth.css" />

    <!-- Helpers -->
    <script src="../../assets/vendor/js/helpers.js"></script>
    <script src="../../assets/js/config.js"></script>
</head>

<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <!-- Card -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="md-4"
                            style="margin-top: 0; display: flex; align-items: center; justify-content: center;">
                            <img src="../../assets/img/logo/school-building.png" alt="Logo" width="100" height="100" />
                        </div>
                        <div class="app-brand justify-content-center"
                            style="display: flex; align-items: center; justify-content: center;">
                            <div class="text-center md-8" style="margin-top: 0;">
                                <p class="mb-1 mt-2" style="font-size: 20px; font-weight: 700; color: #1a202c;">Hệ thống
                                    Quản lý Đặt phòng</p>
                                <p class="mb-0 mt-2" style="font-size: 16px; color: #718096;">Trường Đại học Mỏ - Địa
                                    chất</p>
                            </div>
                        </div>

                        <!-- Hiển thị lỗi chung -->
                        <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            <h6 class="alert-heading mb-2"><i class="bx bx-exclamation-circle"></i> Lỗi đăng nhập</h6>

                            <?php foreach ($_SESSION['errors'] as $error): ?>
                            <div class="mb-1"><small><?= htmlspecialchars($error) ?></small></div>
                            <?php endforeach; ?>

                        </div>
                        <?php unset($_SESSION['errors']); ?>
                        <?php endif; ?>

                        <!-- Form đăng nhập -->
                        <form id="formAuthentication" class="mb-3" action="process_login.php" method="POST">

                            <!-- Username / Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email hoặc Tên Đăng Nhập</label>
                                <input type="text" class="form-control" id="email" name="email-username"
                                    placeholder="Nhập email hoặc tên đăng nhập" autofocus />

                                <?php if (isset($_SESSION['errors_field']['email'])): ?>
                                <small class="text-danger d-block mt-1">
                                    <i class="bx bx-x-circle"></i>
                                    <?= htmlspecialchars($_SESSION['errors_field']['email']) ?>
                                </small>
                                <?php endif; ?>
                            </div>

                            <!-- Password -->
                            <div class="mb-3 form-password-toggle">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label" for="password">Mật Khẩu</label>
                                </div>

                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" class="form-control" name="password"
                                        placeholder="••••••••••••" aria-describedby="password" />
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>

                                <?php if (isset($_SESSION['errors_field']['password'])): ?>
                                <small class="text-danger d-block mt-1">
                                    <i class="bx bx-x-circle"></i>
                                    <?= htmlspecialchars($_SESSION['errors_field']['password']) ?>
                                </small>
                                <?php endif; ?>
                            </div>

                            <!-- Submit -->
                            <div class="mb-3">
                                <button class="btn btn-primary d-grid w-100" type="submit">Đăng Nhập</button>
                            </div>
                        </form>
                        <?php
							unset($_SESSION['errors']);
							unset($_SESSION['errors_field']);
							unset($_SESSION['email_username']);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
</body>

</html>