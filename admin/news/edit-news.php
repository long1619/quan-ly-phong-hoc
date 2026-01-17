<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';
require_once __DIR__ . '/../../helpers/helpers.php';

$userRole = $_SESSION['user_role'] ?? '';

// Kiểm tra quyền sửa tin tức
if (!checkPermission($conn, $userRole, 'edit_news')) {
    echo "<script>alert('Bạn không có quyền chỉnh sửa tin tức!'); window.location.href='../dashboard/index.php';</script>";
    exit;
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$errors = [];
$title = '';
$content = '';
$image_url = '';

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $title = $row['title'];
        $content = $row['content'];
        $image_url = $row['image_url'];
    } else {
        $_SESSION['errors'] = ['Tin tức không tồn tại!'];
        header('Location: list-news.php');
        exit;
    }
    $stmt->close();
} else {
    $_SESSION['errors'] = ['Thiếu ID tin tức!'];
    header('Location: list-news.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '') $errors[] = 'Vui lòng nhập tiêu đề tin tức.';
    if ($content === '') $errors[] = 'Vui lòng nhập nội dung tin tức.';

    // Xử lý upload ảnh mới (nếu có)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Chỉ cho phép ảnh jpg, jpeg, png, gif, webp.';
        } else {
            $uploadDir = '../../uploads/news/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $filename = uniqid('news_') . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $image_url = 'uploads/news/' . $filename;
            } else {
                $errors[] = 'Tải ảnh lên thất bại.';
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE news SET title=?, content=?, image_url=? WHERE id=?");
        $stmt->bind_param("sssi", $title, $content, $image_url, $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Cập nhật tin tức thành công!';
            header('Location: list-news.php');
            exit;
        } else {
            $errors[] = 'Lỗi khi cập nhật dữ liệu: ' . $conn->error;
        }
        $stmt->close();
    }
}

    $success = $_SESSION['success'] ?? null;
    unset($_SESSION['success']);
    include __DIR__ . '/../common/header.php';
    require_once __DIR__ . '/../common/alert.php';
?>
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        --surface-card: #ffffff;
    }
    .news-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        background: var(--surface-card);
        overflow: hidden;
    }
    .news-card-header {
        background: var(--primary-gradient);
        padding: 24px;
        color: white;
    }
    .form-label {
        font-weight: 700;
        color: #334155;
        font-size: 0.9rem;
        margin-bottom: 8px;
        display: block;
    }
    .form-control, .form-select {
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        padding: 12px 16px;
        transition: all 0.2s;
    }
    .form-control:focus, .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }
    .ck-editor__editable {
        min-height: 300px;
        border-radius: 0 0 10px 10px !important;
    }
    .preview-container {
        border: 2px dashed #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        background: #f8fafc;
        margin-top: 10px;
        position: relative;
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    #imgPreview {
        max-width: 100%;
        max-height: 250px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .btn-save {
        background: var(--primary-gradient);
        border: none;
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 700;
        color: white;
        transition: all 0.3s;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(59, 130, 246, 0.3);
    }
    .btn-cancel {
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
    }
</style>

<!-- CKEditor CDN -->
<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include __DIR__ . '/../common/menu-sidebar.php'; ?>
            <div class="layout-page">
                <?php include __DIR__ . '/../common/navbar.php'; ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <nav aria-label="breadcrumb" class="mb-4">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="list-news.php">Tin tức</a></li>
                                <li class="breadcrumb-item active">Chỉnh sửa</li>
                            </ol>
                        </nav>

                        <div class="news-card">
                            <div class="news-card-header text-white">
                                <h4 class="mb-0 text-white"><i class="bx bx-edit me-2"></i>Chỉnh sửa tin tức</h4>
                                <p class="mb-0 opacity-75">Cập nhật lại nội dung tin tức của bạn.</p>
                            </div>
                            <div class="card-body p-4">
                                <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger border-0 shadow-sm mb-4">
                                    <div class="d-flex">
                                        <i class="bx bx-error-circle me-2 fs-4"></i>
                                        <ul class="mb-0">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?= htmlspecialchars($error) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-lg-8">
                                            <div class="mb-4">
                                                <label for="title" class="form-label">Tiêu đề tin tức <span class="text-danger">*</span></label>
                                                <input class="form-control" type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required />
                                            </div>
                                            <div class="mb-4">
                                                <label for="editor" class="form-label">Nội dung chi tiết <span class="text-danger">*</span></label>
                                                <textarea id="editor" name="content"><?= htmlspecialchars($content) ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-4">
                                                <label class="form-label">Ảnh đại diện</label>
                                                <div class="preview-container" onclick="document.getElementById('image').click()">
                                                    <div id="uploadPlaceholder" style="<?= $image_url ? 'display:none;' : '' ?>">
                                                        <i class="bx bx-cloud-upload fs-1 text-muted"></i>
                                                        <p class="text-muted mt-2 mb-0">Nhấn để thay đổi ảnh</p>
                                                    </div>
                                                    <img id="imgPreview" src="<?= $image_url ? '../../'.$image_url : '' ?>" style="<?= $image_url ? 'display:block;' : 'display:none;' ?>" />
                                                </div>
                                                <input class="form-control d-none" type="file" id="image" name="image" accept="image/*" onchange="previewImage(event)">
                                            </div>
                                            <hr class="my-4">
                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-save">
                                                    <i class="bx bx-check me-1"></i> Lưu thay đổi
                                                </button>
                                                <a href="list-news.php" class="btn btn-outline-secondary btn-cancel">
                                                    Hủy bỏ
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php include __DIR__ . '/../common/footer.php'; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
    // Initialize CKEditor
    ClassicEditor
        .create(document.querySelector('#editor'), {
            toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo' ],
            heading: {
                options: [
                    { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                    { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                    { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' }
                ]
            }
        })
        .catch(error => {
            console.error(error);
        });

    function previewImage(event) {
        const img = document.getElementById('imgPreview');
        const placeholder = document.getElementById('uploadPlaceholder');
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                img.style.display = 'block';
                placeholder.style.display = 'none';
            }
            reader.readAsDataURL(file);
        }
    }
    </script>
    <?php showSuccessAlert($success); ?>
</body>
</html>