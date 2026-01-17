<?php
    require_once __DIR__ . '/../../config/connect.php';
    require_once __DIR__ . '/../../helpers/helpers.php';

    // Lấy user id từ URL
    $userId = $_SESSION['user_id'] ? intval($_SESSION['user_id']) : 0;
    $userLoginId = getUserById($conn, $userId);
?>
<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar" style="z-index: 1;">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <!-- User -->
            <h5 style="padding-top: 8%;"><?php echo $userLoginId['full_name']; ?></h5>

            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="<?php echo !empty($userLoginId['avatar']) ? '../../assets/' . htmlspecialchars($userLoginId['avatar']) : ''; ?>" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="../user/edit-user.php?id=<?php echo $userLoginId['id']; ?>">
                            <i class="bx bx-user me-2"></i>
                            <span class="align-middle">Chỉnh sửa thông tin</span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../auth/logout.php">
                            <i class="bx bx-power-off me-2"></i>
                            <span class="align-middle">Đăng xuất</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>