<?php
require_once __DIR__ . '/../../helpers/helpers.php';
$userRole = $_SESSION['user_role'] ?? '';
$userSessionId = $_SESSION['user_id'] ?? 0;

// Function to check if a menu item is active
function is_active($keywords) {
    if (!is_array($keywords)) {
        $keywords = [$keywords];
    }
    $current_url = $_SERVER['REQUEST_URI'];
    foreach ($keywords as $keyword) {
        if (stripos($current_url, $keyword) !== false) {
            return 'active';
        }
    }
    return '';
}

// Function to check if a submenu should be open
function is_open($keywords) {
    if (!is_array($keywords)) {
        $keywords = [$keywords];
    }
    $current_url = $_SERVER['REQUEST_URI'];
    foreach ($keywords as $keyword) {
        if (stripos($current_url, $keyword) !== false) {
            return 'active open';
        }
    }
    return '';
}
?>
<style>
/* --- Sidebar Main --- */
.layout-menu {
    position: relative; /* Ensure absolute children are relative to this */
    display: flex;
    flex-direction: column; /* Main axis vertical */
    height: 100% !important;
}

.menu-inner {
    flex-grow: 1; /* Allow menu to take available space */
    overflow-y: auto; /* Scrollable menu items */
    overflow-x: hidden;
    padding-bottom: 100px; /* Space for the role box */
}

/* --- Brand/Logo Area --- */
.app-brand {
    padding: 1rem 1.5rem;
    height: auto !important; /* Override template defaults if needed */
    margin-bottom: 0.5rem;
    display: flex;
    justify-content: center;
    align-items: center;
    text-decoration: none;
}
.brand-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}
.brand-logo-circle {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    padding: 8px;
    border-radius: 20%;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
}
.brand-title {
    font-size: 14px;
    font-weight: 800;
    color: #566a7f;
    margin-top: 4px;
}
.brand-subtitle {
    font-size: 10px;
    font-weight: 600;
    color: #a1a1aa;
    text-transform: uppercase;
}

/* --- Menu Headers --- */
.menu-header {
    margin-top: 1rem;
    margin-bottom: 0.25rem;
    padding: 0 1rem; /* Match menu item indentation */
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #b4bdce;
    display: flex;
    align-items: center;
    justify-content: flex-start !important; /* Force Left Align */
}
/* Optional: Decoration line */
.menu-header::before {
    content: '';
    display: block;
    width: 12px;
    height: 2px;
    background-color: #e2e8f0;
    margin-right: 12px;
}

/* --- Menu Items --- */
.menu-item {
    margin: 0.1rem 0.5rem; /* Reduced margin to prevent overflow */
    width: calc(100% - 1rem); /* Explicit width calculation */
}

.menu-link {
    display: flex;
    align-items: center;
    padding: 0.7rem 1rem; /* Standard padding */
    color: #697a8d;
    text-decoration: none;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    white-space: nowrap; /* Prevent text wrapping */
    position: relative; /* Added to anchor absolute children like the chevron */
}

.menu-icon {
    flex-shrink: 0; /* Prevent icon from shrinking */
    font-size: 1.3rem;
    margin-right: 0.8rem;
    text-align: center;
    width: 1.3rem;
    color: #8f9bb3; /* Default icon color */
}

/* Hover & Active */
.menu-item:not(.active) .menu-link:hover {
    background-color: #f3f4f6;
    color: #3b82f6;
}
.menu-item:not(.active) .menu-link:hover .menu-icon {
    color: #3b82f6;
}

.menu-item.active > .menu-link {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff !important;
    box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
}
.menu-item.active > .menu-link .menu-icon {
    color: #fff !important;
}

/* --- Submenu --- */
.menu-sub {
    padding: 0;
    margin: 0;
    display: none; /* Changed from height transition for better stability with template JS */
    background: transparent;
}
.menu-item.open > .menu-sub {
    display: block;
}

.menu-sub .menu-item {
    margin: 0.1rem 0.5rem 0.1rem 0.5rem;
}
.menu-sub .menu-link {
    padding-left: 3rem; /* Indent submodules */
    font-size: 0.9rem;
}
.menu-sub .menu-item.active .menu-link {
    background: rgba(59, 130, 246, 0.08); /* Light blue bg for active child */
    color: #3b82f6 !important;
    font-weight: 600;
    box-shadow: none;
}
.menu-sub .menu-item.active .menu-link:before {
    display: none; /* Remove dot if using background highlight */
}

/* --- Toggles --- */
/* Hide any template-provided arrows that might be floating around */
.menu-item.menu-toggle::after {
    display: none !important;
}

.menu-toggle > .menu-link:after {
    content: "";
    display: block;
    position: absolute;
    right: 1.2rem;
    top: 50%;
    width: 6px;
    height: 6px;
    border-bottom: 2px solid #a1a1aa;
    border-right: 2px solid #a1a1aa;
    transform: translateY(-50%) rotate(-45deg); /* Default: Points Right */
    transition: all 0.3s ease;
}

.menu-item.open > .menu-link:after {
    transform: translateY(-70%) rotate(45deg); /* Open: Points Down */
    border-color: #3b82f6;
}

.menu-item.active > .menu-link:after {
    border-color: #fff !important;
}

/* Ensure non-toggles do not show any arrow from our custom rules */
.menu-item:not(.menu-toggle) > .menu-link:after {
    display: none !important;
}

/* --- Role Box (Premium Static Design) --- */
.role-box-container {
    position: absolute;
    bottom: 20px;
    left: 0;
    width: 100%;
    padding: 0 1.2rem;
    pointer-events: none;
}
.role-box-card {
    pointer-events: auto;
    background: #ffffff;
    /* Premium border with subtle gradient feel */
    border: 1px solid #f0f0f0;
    border-left: 4px solid #3b82f6;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 15px;
    /* Soft, colorful shadow */
    box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.25);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    overflow: hidden;
}

/* Subtle background decoration */
.role-box-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 70%);
    z-index: 0;
}

.role-box-card:hover {
    /* No movement, just style updates */
    box-shadow: 0 15px 35px -10px rgba(59, 130, 246, 0.4);
    border-color: #93c5fd;
}

.role-icon {
    width: 40px;
    height: 40px;
    /* Gradient Icon Background */
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
    z-index: 1;
}

.role-info {
    z-index: 1;
    display: flex;
    flex-direction: column;
}

.role-info span {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #94a3b8;
    font-weight: 700;
    margin-bottom: 2px;
}

.role-info h6 {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: #334155;
    line-height: 1.2;
}


/* --- Badge --- */
.badge-notif {
    background-color: #ff3d1f;
    color: white;
    font-size: 0.7rem;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: auto;
}
</style>

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

    <!-- Brand -->
    <div class="app-brand demo">
        <a href="../dashboard/index.php" class="brand-wrapper">
            <div class="brand-logo-circle">
                <img src="../../assets/img/logo/school-building.png" alt="Logo" width="50" height="50" />
            </div>
            <span class="brand-title">Quản lý Đặt Phòng</span>
        </a>
    </div>

    <!-- Scrollable Menu Area -->
    <ul class="menu-inner">

        <!-- Dashboard -->
        <li class="menu-item <?php echo is_active('dashboard'); ?>">
            <a href="../dashboard/index.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
            </a>
        </li>

        <!-- User Management -->
        <?php if (checkPermission($conn, $userRole, 'view_user')): ?>
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Người dùng</span>
        </li>
        <li class="menu-item menu-toggle <?php echo is_open(['user', 'list-user', 'add-user']); ?>">
            <a href="javascript:void(0);" class="menu-link">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div data-i18n="Quản lý người dùng">Quản lý người dùng</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo is_active('list-user.php'); ?>">
                    <a href="../user/list-user.php" class="menu-link">
                        <div data-i18n="Danh sách">Danh sách người dùng</div>
                    </a>
                </li>
                <?php if (checkPermission($conn, $userRole, 'add_user')): ?>
                <li class="menu-item <?php echo is_active('add-user.php'); ?>">
                    <a href="../user/add-user.php" class="menu-link">
                        <div data-i18n="Thêm mới">Thêm người dùng</div>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <li class="menu-item <?php echo is_active('permission.php'); ?>">
            <a href="../settings/permission.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-cog"></i>
                <div data-i18n="Phân quyền">Phân quyền</div>
            </a>
        </li>
        <?php endif; ?>
        <!-- Room & Booking -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Phòng & Đặt Lịch</span>
        </li>

        <!-- Room Category Management -->
        <?php if (checkPermission($conn, $userRole, 'view_room_type')): ?>
        <li class="menu-item menu-toggle <?php echo is_open(['room-type', 'list-type-room']); ?>">
            <a href="javascript:void(0);" class="menu-link">
                <i class="menu-icon tf-icons bx bx-category"></i>
                <div data-i18n="Quản lý danh mục phòng">Quản lý danh mục</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo is_active('list-type-room.php'); ?>">
                    <a href="../room-type/list-type-room.php" class="menu-link">
                        <div data-i18n="Loại phòng">Loại phòng</div>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <!-- Room Management -->
        <?php if (checkPermission($conn, $userRole, 'view_room')): ?>
        <li class="menu-item menu-toggle <?php echo is_open(['list-room', 'calendar-room', '/room/']); ?>">
            <a href="javascript:void(0);" class="menu-link">
                <i class="menu-icon tf-icons bx bx-building-house"></i>
                <div data-i18n="Quản lý phòng">Quản lý phòng</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo is_active('list-room.php'); ?>">
                    <a href="../room/list-room.php" class="menu-link">
                        <div data-i18n="Danh sách phòng">Danh sách phòng</div>
                    </a>
                </li>
                <li class="menu-item <?php echo is_active('calendar-room.php'); ?>">
                    <a href="../booking/calendar-room.php" class="menu-link">
                        <div data-i18n="Lịch phòng">Xem lịch phòng</div>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <?php
            // Calculate Pending Count
            $pending_count = 0;
            if (isset($conn)) {
                $checkTable = $conn->query("SHOW TABLES LIKE 'bookings'");
                if($checkTable && $checkTable->num_rows > 0) {
                     $sql_pending = "SELECT COUNT(*) as total FROM bookings WHERE status = 'cho_duyet'";
                     $result_pending = $conn->query($sql_pending);
                     if ($result_pending && $row = $result_pending->fetch_assoc()) {
                         $pending_count = $row['total'];
                     }
                }
            }
        ?>
        <?php if (checkPermission($conn, $userRole, 'approve_booking')): ?>
        <li class="menu-item <?php echo is_active('approve-room.php'); ?>">
            <a href="../approve/approve-room.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-check-shield"></i>
                <div data-i18n="Phê duyệt">Phê duyệt đơn</div>
                <?php if ($pending_count > 0): ?>
                    <span class="badge-notif"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endif; ?>

        <?php if (checkPermission($conn, $userRole, 'view_history')): ?>
        <li class="menu-item <?php echo is_active('list-canceled-bookings.php'); ?>">
            <a href="../cancel/list-canceled-bookings.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-x-circle"></i>
                <div data-i18n="Đơn đã hủy">Đơn đã hủy</div>
            </a>
        </li>

        <li class="menu-item <?php echo is_active('history-booking.php'); ?>">
            <a href="../history/history-booking.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-history"></i>
                <div data-i18n="Lịch sử">Lịch sử đặt phòng</div>
            </a>
        </li>
        <?php endif; ?>

        <!-- System -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Hệ thống</span>
        </li>
        <?php if (checkPermission($conn, $userRole, 'view_news')): ?>
        <li class="menu-item <?php echo is_active('list-news.php'); ?>">
            <a href="../news/list-news.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-news"></i>
                <div data-i18n="Tin tức">Tin tức</div>
            </a>
        </li>
        <?php endif; ?>

        <?php if (checkPermission($conn, $userRole, 'use_ai')): ?>
        <li class="menu-item <?php echo is_active('ai-assistant.php'); ?>">
            <a href="../ai-assistant/ai-assistant.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-bot"></i>
                <div data-i18n="Trợ lý AI">Trợ lý AI</div>
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <!-- Fixed Role Box -->
    <div class="role-box-container">
        <div class="role-box-card">
            <div class="role-icon">
                <i class="bx bx-id-card"></i>
            </div>
            <div class="role-info">
                <span>Bạn đang là</span>
                <h6>
                    <?php
                        $role_map = [
                            'admin' => 'Admin',
                            'giang_vien' => 'Giảng Viên',
                            'sinh_vien' => 'Sinh Viên'
                        ];
                        echo isset($role_map[$_SESSION['user_role']]) ? $role_map[$_SESSION['user_role']] : '';
                    ?>
                </h6>
            </div>
        </div>
    </div>

</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Menu toggle logic
    const menuToggles = document.querySelectorAll('.menu-toggle > .menu-link');

    menuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const menuItem = this.parentElement;
            menuItem.classList.toggle('open');
        });
    });

    // Fix: Force navigation for Child Links (in case template JS blocks them)
    // We target links inside .menu-sub that have a real href
    const childLinks = document.querySelectorAll('.menu-sub .menu-link');
    childLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && href !== 'javascript:void(0);' && href !== '#') {
                // If the link is not just a placeholder, allow standard navigation.
                // Forces browser to go to that URL, overriding any preventDefault from other scripts.
                window.location.href = href;
            }
        });
    });

    // Keep active menu open
    const activeItem = document.querySelector('.menu-item.active');
    if (activeItem) {
        // If nested, open parent
        const parentSub = activeItem.closest('.menu-sub');
        if (parentSub) {
            parentSub.parentElement.classList.add('open');
        }
    }
});
</script>