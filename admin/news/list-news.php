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

// Kiểm tra quyền xem tin tức
if (!checkPermission($conn, $userRole, 'view_news')) {
    echo "<script>alert('Bạn không có quyền xem tin tức!'); window.location.href='../dashboard/index.php';</script>";
    exit;
}

require_once __DIR__ . '/../common/alert.php';
$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// Lấy danh sách tin tức
$query = "SELECT n.*, u.full_name AS author FROM news n LEFT JOIN users u ON n.created_by = u.id ORDER BY n.created_at DESC";
$result = $conn->query($query);
$newsList = [];
while ($row = $result->fetch_assoc()) {
    $newsList[] = $row;
}
?>
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }
    .table-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        background: #fff;
        overflow: hidden;
    }
    .table thead th {
        background: #f8fafc;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        font-weight: 700;
        color: #64748b;
        padding: 16px 24px;
        border-bottom: 2px solid #f1f5f9;
        white-space: nowrap;
    }
    .table tbody td {
        padding: 16px 24px;
        vertical-align: middle;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem;
    }
    .news-title-cell {
        max-width: 350px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 600;
        color: #1e293b;
    }
    .status-badge {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-block;
    }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-secondary { background: #f1f5f9; color: #475569; }

    .news-img-thumb {
        width: 80px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        background: #f8fafc;
    }
    .btn-action {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        transition: all 0.2s;
        border: none;
        margin: 0 2px;
        text-decoration: none !important;
    }
    .btn-view { background: #eff6ff; color: #2563eb; }
    .btn-edit { background: #fffbeb; color: #d97706; }
    .btn-delete { background: #fef2f2; color: #dc2626; }

    .btn-view:hover { background: #2563eb; color: #fff; }
    .btn-edit:hover { background: #d97706; color: #fff; }
    .btn-delete:hover { background: #dc2626; color: #fff; }

    .add-news-btn {
        background: var(--primary-gradient);
        color: white !important;
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        transition: all 0.3s;
        border: none;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    .add-news-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(59, 130, 246, 0.4);
    }
    .news-content-preview {
        max-width: 200px;
        font-size: 0.8rem;
        color: #64748b;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
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
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="fw-bold mb-1">📰 Quản lý Tin tức</h4>
                                <p class="text-muted mb-0">Xem và cập nhật các thông báo, tin tức hệ thống.</p>
                            </div>
                            <?php if (checkPermission($conn, $userRole, 'add_news')): ?>
                            <a href="add-news.php" class="btn add-news-btn">
                                <i class="bx bx-plus me-1"></i> Thêm tin tức
                            </a>
                            <?php endif; ?>
                        </div>

                        <div class="table-card mt-4">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Ảnh</th>
                                            <th>Tiêu đề</th>
                                            <th>Tác giả</th>
                                            <th>Thời gian</th>
                                            <th class="text-center">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($newsList)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <div class="text-muted">
                                                    <i class="bx bx-folder-open fs-1 d-block mb-2"></i>
                                                    Chưa có tin tức nào được tìm thấy
                                                </div>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($newsList as $news): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($news['image_url']): ?>
                                                        <img src="../../<?= htmlspecialchars($news['image_url']) ?>" class="news-img-thumb" alt="Thumb">
                                                    <?php else: ?>
                                                        <div class="news-img-thumb d-flex align-items-center justify-content-center">
                                                            <i class="bx bx-image text-muted fs-4"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="news-title-cell" title="<?= htmlspecialchars($news['title']) ?>">
                                                        <?= htmlspecialchars($news['title']) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-xs me-2">
                                                            <span class="avatar-initial rounded-circle bg-label-primary text-uppercase" style="font-size: 10px; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                                                                <?= substr($news['author'], 0, 1) ?>
                                                            </span>
                                                        </div>
                                                        <span><?= htmlspecialchars($news['author']) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-muted" style="font-size: 0.85rem;">
                                                        <i class="bx bx-calendar me-1"></i><?= date('d/m/Y', strtotime($news['created_at'])) ?><br>
                                                        <i class="bx bx-time-five me-1"></i><?= date('H:i', strtotime($news['created_at'])) ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <a href="view-news.php?id=<?= $news['id'] ?>" class="btn-action btn-view" title="Xem chi tiết">
                                                        <i class="bx bx-show-alt"></i>
                                                    </a>
                                                    <?php if (checkPermission($conn, $userRole, 'edit_news')): ?>
                                                    <a href="edit-news.php?id=<?= $news['id'] ?>" class="btn-action btn-edit" title="Chỉnh sửa">
                                                        <i class="bx bx-edit-alt"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php if (checkPermission($conn, $userRole, 'delete_news')): ?>
                                                    <a href="delete-news.php?id=<?= $news['id'] ?>"
                                                       class="btn-action btn-delete btn-delete-news"
                                                       data-id="<?= $news['id'] ?>"
                                                       title="Xóa">
                                                        <i class="bx bx-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
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
    <?php showSuccessAlert($success); ?>
</body>
</html>