<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

include __DIR__ . '/../common/header.php';
require_once __DIR__ . '/../../helpers/helpers.php';

$userRole = $_SESSION['user_role'] ?? '';
$id = $_GET['id'] ?? 0;

// Kiểm tra quyền xem tin tức
if (!checkPermission($conn, $userRole, 'view_news')) {
    echo "<script>alert('Bạn không có quyền xem tin tức!'); window.location.href='../dashboard/index.php';</script>";
    exit;
}

// Lấy thông tin tin tức
$query = "SELECT n.*, u.full_name AS author FROM news n LEFT JOIN users u ON n.created_by = u.id WHERE n.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$news = $stmt->get_result()->fetch_assoc();

if (!$news) {
    header('Location: list-news.php');
    exit;
}
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        --surface-color: #ffffff;
    }

    .news-detail-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .news-header-card {
        background: var(--surface-color);
        border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04);
        padding: 40px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(226, 232, 240, 0.8);
    }

    .news-category-badge {
        background: #f1f5f9;
        color: #64748b;
        padding: 6px 16px;
        border-radius: 100px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 20px;
        display: inline-block;
    }

    .news-title-heavy {
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.3;
        margin-bottom: 20px;
    }

    .meta-info-strip {
        display: flex;
        align-items: center;
        gap: 24px;
        padding-top: 24px;
        border-top: 1px solid #f1f5f9;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #64748b;
        font-size: 0.9rem;
    }

    .meta-item i {
        font-size: 1.25rem;
        color: #3b82f6;
    }

    .news-header-thumb {
        width: 180px;
        height: 180px;
        object-fit: cover;
        border-radius: 20px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border: 4px solid #fff;
    }

    .header-image-wrapper {
        flex-shrink: 0;
    }

    .news-body-content {
        background: var(--surface-color);
        border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04);
        padding: 50px;
        line-height: 1.8;
        color: #334155;
        font-size: 1.1rem;
        border: 1px solid rgba(226, 232, 240, 0.8);
    }

    .news-body-content h1, .news-body-content h2, .news-body-content h3 {
        color: #0f172a;
        margin-top: 2rem;
        margin-bottom: 1rem;
        font-weight: 700;
    }

    .news-body-content img {
        max-width: 100%;
        border-radius: 16px;
        margin: 2rem 0;
    }

    .action-floating-bar {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        padding: 12px 30px;
        border-radius: 100px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        display: flex;
        gap: 15px;
        z-index: 100;
        border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .btn-action-float {
        padding: 10px 24px;
        border-radius: 100px;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        text-decoration: none;
    }

    .btn-action-float.back {
        background: #f1f5f9;
        color: #475569;
    }

    .btn-action-float.edit {
        background: var(--primary-gradient);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-action-float:hover {
        transform: translateY(-5px);
    }





    @media (max-width: 768px) {
        .news-header-card .d-flex { flex-direction: column-reverse; }
        .news-header-thumb { width: 100%; height: 200px; }
        .news-title-heavy { font-size: 1.6rem; }
        .meta-info-strip { flex-direction: column; align-items: flex-start; gap: 12px; }
        .news-body-content { padding: 30px; }
    }
</style>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include __DIR__ . '/../common/menu-sidebar.php'; ?>
            <div class="layout-page">
                <?php include __DIR__ . '/../common/navbar.php'; ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">

                        <div class="news-detail-container">
                            <!-- Header Card -->
                            <div class="news-header-card">
                                <div class="d-flex justify-content-between align-items-start gap-4">
                                    <div class="flex-grow-1">
                                        <span class="news-category-badge">Thông báo hệ thống</span>
                                        <h1 class="news-title-heavy"><?= htmlspecialchars($news['title']) ?></h1>

                                        <div class="meta-info-strip">
                                            <div class="meta-item">
                                                <i class="bx bxs-user-circle"></i>
                                                <span>Đăng bởi: <strong><?= htmlspecialchars($news['author']) ?></strong></span>
                                            </div>
                                            <div class="meta-item">
                                                <i class="bx bxs-calendar"></i>
                                                <span><?= date('d \t\h\á\n\g m, Y', strtotime($news['created_at'])) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($news['image_url']): ?>
                                    <div class="header-image-wrapper">
                                        <img src="../../<?= htmlspecialchars($news['image_url']) ?>" class="news-header-thumb" alt="Thumb">
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Main Content -->
                            <div class="news-body-content">
                                <?= $news['content'] ?>
                            </div>

                            <div style="height: 100px;"></div> <!-- Spacer for floating bar -->
                        </div>

                        <!-- Floating Action Bar -->
                        <div class="action-floating-bar">
                            <a href="list-news.php" class="btn-action-float back">
                                <i class="bx bx-left-arrow-alt"></i> Quay lại
                            </a>
                            <?php if (checkPermission($conn, $userRole, 'edit_news')): ?>
                            <a href="edit-news.php?id=<?= $news['id'] ?>" class="btn-action-float edit">
                                <i class="bx bx-edit-alt"></i> Chỉnh sửa
                            </a>
                            <?php endif; ?>
                            <?php if (checkPermission($conn, $userRole, 'delete_news')): ?>
                            <a href="delete-news.php?id=<?= $news['id'] ?>"
                               class="btn-action-float btn-delete-news"
                               style="background: #fee2e2; color: #dc2626;"
                               data-id="<?= $news['id'] ?>"
                               title="Xóa">
                                <i class="bx bx-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>

                    </div>
                    <?php include __DIR__ . '/../common/footer.php'; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Scripts -->
    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php require_once __DIR__ . '/../common/alert.php'; ?>

    <script>
        $(document).ready(function() {
            setupDeleteConfirmation(
                '.btn-delete-news',
                'delete-news.php?id={id}',
                'id',
                'Xác nhận xóa tin tức?',
                'Hành động này sẽ xóa vĩnh viễn tin tức và không thể hoàn tác.'
            );
        });
    </script>
</body>
</html>