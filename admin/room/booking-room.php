<?php
    session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../auth/login.php');
        exit;
    }
    include __DIR__ . '/../common/header.php';
    require_once __DIR__ . '/../../config/connect.php';

    $idLogin = $_SESSION['user_id'];
    // Lấy danh sách user từ database
    $sql = "SELECT * FROM users WHERE id != $idLogin ORDER BY created_at DESC";
    $result = $conn->query($sql);
?>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Public Sans', sans-serif;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }

    .modal-wrapper {
      background: white;
      border-radius: 12px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      max-width: 560px;
      width: 100%;
      overflow: hidden;
      animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Header */
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 24px;
      border-bottom: 1px solid #f0f0f0;
    }

    .modal-title {
      font-size: 16px;
      font-weight: 700;
      color: #333;
    }

    .close-btn {
      background: none;
      border: none;
      font-size: 28px;
      color: #999;
      cursor: pointer;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: all 0.2s;
    }

    .close-btn:hover {
      background: #f5f5f5;
      color: #333;
    }

    /* Steps */
    .steps-container {
      display: flex;
      justify-content: space-around;
      align-items: center;
      padding: 24px 20px;
      background: #fafafa;
      position: relative;
    }

    .steps-wrapper {
      display: flex;
      justify-content: space-around;
      align-items: center;
      width: 100%;
      position: relative;
    }

    .step-connector-line {
      position: absolute;
      top: 20px;
      height: 2px;
      background-color: #ddd;
      width: 25%;
      z-index: 1;
    }

    .step-connector-line.first {
      left: 20%;
    }

    .step-connector-line.second {
      left: 55%;
    }

    .step-connector-line.first.active {
      background-color: #2563eb;
    }

    .step-connector-line.second.active {
      background-color: #2563eb;
    }

    .step-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      position: relative;
      z-index: 2;
    }

    .step-badge {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #e0e0e0;
      color: #999;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 16px;
      transition: all 0.3s;
      flex-shrink: 0;
    }

    .step-item.active .step-badge {
      background: #2563eb;
      color: white;
      box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
    }

    .step-item.completed .step-badge {
      background: #2563eb;
      color: white;
    }

    .step-label {
      font-size: 13px;
      color: #999;
      font-weight: 500;
      white-space: nowrap;
    }

    .step-item.active .step-label {
      color: #2563eb;
      font-weight: 600;
    }

    .step-item.completed .step-label {
      color: #2563eb;
    }

    /* Body */
    .modal-body {
      padding: 30px;
      min-height: 400px;
    }

    .form-section {
      display: none;
    }

    .form-section.active {
      display: block;
      animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }

    .form-group {
      margin-bottom: 24px;
    }

    .form-label {
      display: block;
      font-size: 14px;
      font-weight: 600;
      color: #333;
      margin-bottom: 10px;
    }

    .form-label.required::after {
      content: " *";
      color: #dc3545;
    }

    .form-control,
    .form-select {
      width: 100%;
      padding: 11px 13px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      font-family: inherit;
      background: white;
      transition: all 0.2s;
      color: #333;
    }

    .form-control::placeholder,
    .form-select::placeholder {
      color: #aaa;
    }

    .form-control:focus,
    .form-select:focus {
      outline: none;
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    textarea.form-control {
      resize: vertical;
      min-height: 100px;
      font-family: inherit;
    }

    .form-select {
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23333' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 13px center;
      padding-right: 36px;
    }

    .time-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .time-input-group {
      position: relative;
    }

    .time-input-group input {
      width: 100%;
      padding: 11px 13px;
      padding-right: 40px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      transition: all 0.2s;
    }

    .time-input-group input:focus {
      outline: none;
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .time-input-icon {
      position: absolute;
      right: 13px;
      top: 50%;
      transform: translateY(-50%);
      color: #999;
      font-size: 16px;
      pointer-events: none;
    }

    .time-labels {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-top: 8px;
    }

    .time-label {
      font-size: 12px;
      color: #999;
    }

    /* Info Box */
    .info-box {
      background: #f0f6ff;
      border: 1px solid #d4e4ff;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 24px;
    }

    .info-box-title {
      font-size: 14px;
      font-weight: 600;
      color: #333;
      margin-bottom: 16px;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 12px;
      margin-bottom: 12px;
      border-bottom: 1px solid #e0eaff;
    }

    .info-row:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }

    .info-label {
      font-size: 13px;
      color: #666;
      font-weight: 500;
    }

    .info-value {
      font-size: 13px;
      color: #333;
      font-weight: 600;
      text-align: right;
    }

    /* Checkbox */
    .checkbox-group {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 24px;
    }

    .checkbox-item {
      display: flex;
      align-items: flex-start;
      gap: 10px;
    }

    .checkbox-item input[type="checkbox"] {
      width: 18px;
      height: 18px;
      cursor: pointer;
      margin-top: 2px;
      flex-shrink: 0;
      accent-color: #2563eb;
    }

    .checkbox-item label {
      cursor: pointer;
      font-size: 13px;
      color: #666;
      margin: 0;
      line-height: 1.4;
    }

    /* Footer */
    .modal-footer {
      padding: 20px 30px;
      border-top: 1px solid #f0f0f0;
      display: flex;
      justify-content: space-between;
      gap: 12px;
      background: #fafafa;
    }

    .btn {
      padding: 10px 24px;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-secondary {
      background: white;
      color: #333;
      border: 1px solid #ddd;
    }

    .btn-secondary:hover {
      background: #f5f5f5;
      border-color: #bbb;
    }

    .btn-secondary:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .btn-primary {
      background: #2563eb;
      color: white;
    }

    .btn-primary:hover {
      background: #1d4ed8;
    }

    .btn-primary:active {
      transform: scale(0.98);
    }

    /* Responsive */
    @media (max-width: 600px) {
      .modal-wrapper {
        border-radius: 8px;
      }

      .modal-body {
        padding: 20px;
      }

      .time-row {
        grid-template-columns: 1fr;
      }

      .time-labels {
        grid-template-columns: 1fr;
      }

      .step-label {
        font-size: 11px;
      }

      .info-row {
        flex-direction: column;
        align-items: flex-start;
      }

      .info-value {
        text-align: left;
        margin-top: 4px;
      }
    }
  </style>
</head>

<body>
    <!-- Modal Container -->
  <div class="modal-wrapper">
    <!-- Header -->
    <div class="modal-header">
      <h3 class="modal-title">Đặt phòng mới</h3>
      <button class="close-btn" onclick="closeModal()" title="Đóng">×</button>
    </div>

    <!-- Steps Indicator -->
    <div class="steps-container">
      <div class="steps-wrapper">
        <div class="step-connector-line first" id="connector-1"></div>
        <div class="step-connector-line second" id="connector-2"></div>

        <div class="step-item active" id="step-1">
          <div class="step-badge">1</div>
          <span class="step-label">Chọn phòng</span>
        </div>
        <div class="step-item" id="step-2">
          <div class="step-badge">2</div>
          <span class="step-label">Chi tiết</span>
        </div>
        <div class="step-item" id="step-3">
          <div class="step-badge">3</div>
          <span class="step-label">Xác nhận</span>
        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="modal-body">
      <!-- Form -->
      <form id="bookingForm" onsubmit="handleSubmit(event)">

        <!-- ===== STEP 1: Chọn Phòng ===== -->
        <div class="form-section active" id="form-step-1">
          <!-- Chọn Phòng -->
          <div class="form-group">
            <label class="form-label required">Chọn phòng</label>
            <select class="form-select" id="roomSelect" required onchange="updateSuccessAlert()">
              <option value="">-- Chọn phòng --</option>
              <option value="A101">A101 - Phòng học A101 (Sức chứa: 60)</option>
              <option value="A201">A201 - Lab CNTT 01 (Sức chứa: 40)</option>
              <option value="A102">A102 - Phòng học A102 (Sức chứa: 50)</option>
              <option value="A202">A202 - Phòng hội thảo (Sức chứa: 30)</option>
            </select>
          </div>

          <!-- Ngày sử dụng -->
          <div class="form-group">
            <label class="form-label required">Ngày sử dụng</label>
            <div style="position: relative;">
              <input type="date" class="form-control" id="bookingDate" required />
              <i class="bx bx-calendar time-input-icon"></i>
            </div>
          </div>

          <!-- Giờ -->
          <div class="form-group">
            <label class="form-label required">Giờ</label>
            <div class="time-row">
              <div class="time-input-group">
                <input type="time" id="startTime" placeholder="--:-- --" required />
                <i class="bx bx-time time-input-icon"></i>
              </div>
              <div class="time-input-group">
                <input type="time" id="endTime" placeholder="--:-- --" required />
                <i class="bx bx-time time-input-icon"></i>
              </div>
            </div>
            <div class="time-labels">
              <span class="time-label">Giờ bắt đầu</span>
              <span class="time-label">Giờ kết thúc</span>
            </div>
          </div>
        </div>

        <!-- ===== STEP 2: Chi Tiết ===== -->
        <div class="form-section" id="form-step-2">
          <!-- Mục đích sử dụng -->
          <div class="form-group">
            <label class="form-label required">Mục đích sử dụng</label>
            <textarea class="form-control" id="purpose" placeholder="Ví dụ: Hop CLB Lập trình hàng tuần" required></textarea>
          </div>

          <!-- Số người tham gia -->
          <div class="form-group">
            <label class="form-label required">Số người tham gia dự kiến</label>
            <input type="number" class="form-control" id="attendees" value="25" min="1" required />
          </div>

          <!-- Số điện thoại -->
          <div class="form-group">
            <label class="form-label required">Số điện thoại liên hệ</label>
            <input type="tel" class="form-control" id="phone" placeholder="0912345678" required />
          </div>

          <!-- Ghi chú thêm -->
          <div class="form-group">
            <label class="form-label">Ghi chú thêm</label>
            <textarea class="form-control" id="notes" placeholder="Cần máy chiếu và micro..."></textarea>
          </div>
        </div>

        <!-- ===== STEP 3: Xác Nhận ===== -->
        <div class="form-section" id="form-step-3">
          <!-- Info Box -->
          <div class="info-box">
            <div class="info-box-title">Thông tin đặt phòng</div>
            <div class="info-row">
              <span class="info-label">Phòng:</span>
              <span class="info-value" id="confirm-room">A101 - Phòng học A101</span>
            </div>
            <div class="info-row">
              <span class="info-label">Ngày:</span>
              <span class="info-value" id="confirm-date">--/--/--</span>
            </div>
            <div class="info-row">
              <span class="info-label">Thời gian:</span>
              <span class="info-value" id="confirm-time">-</span>
            </div>
            <div class="info-row">
              <span class="info-label">Mục đích:</span>
              <span class="info-value" id="confirm-purpose">--</span>
            </div>
          </div>

          <!-- Checkboxes -->
          <div class="checkbox-group">
            <div class="checkbox-item">
              <input type="checkbox" id="confirm-checkbox-1" required />
              <label for="confirm-checkbox-1">Tôi cam kết sử dụng đúng mục đích</label>
            </div>
            <div class="checkbox-item">
              <input type="checkbox" id="confirm-checkbox-2" required />
              <label for="confirm-checkbox-2">Tôi sẽ giữ gìn về sinh và tài sản</label>
            </div>
          </div>
        </div>

      </form>
    </div>

    <!-- Footer -->
    <div class="modal-footer">
      <button class="btn btn-secondary" id="btn-back" onclick="handleBack()" style="display: none;">
        ← Quay lại
      </button>
      <button class="btn btn-primary" id="btn-next" onclick="handleNext()">
        Tiếp tục →
      </button>
    </div>
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

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" />

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <!-- Xuất file -->
    <!-- <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script> -->
    <!-- Hỗ trợ xuất file Excel -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script> -->

    <script>
    $(document).ready(function() {
        var table = $('#userTable').DataTable({
            dom: 'Bfrtip',
            pageLength: 10,
            // buttons: [
            //     { extend: 'copy', className: 'btn btn-outline-secondary' },
            //     { extend: 'excel', className: 'btn btn-outline-secondary' },
            //     { extend: 'csv', className: 'btn btn-outline-secondary' },
            //     { extend: 'pdf', className: 'btn btn-outline-secondary' },
            //     { extend: 'print', className: 'btn btn-outline-secondary' }
            // ],
            columnDefs: [{
                orderable: false,
                targets: [7]
            }],
            language: {
                "zeroRecords": "Không tìm thấy bản ghi phù hợp",
                "infoEmpty": "Không có dữ liệu",
                "info": "Hiển thị _START_ đến _END_ của _TOTAL_ bản ghi",
                "lengthMenu": "Hiển thị _MENU_ bản ghi",
                "search": "Tìm kiếm:",
                "paginate": {
                    "first": "Đầu",
                    "last": "Cuối",
                    "next": "Sau",
                    "previous": "Trước"
                }
            }
        });

        // Filter theo vai trò
        $('#UserRole').on('change', function() {
            var val = $(this).val();
            table.column(3).search(val, true, false).draw();
        });

        // Filter theo trạng thái
        $('#UserStatus').on('change', function() {
            var val = $(this).val();
            table.column(5).search(val, true, false).draw();
        });

        // Tìm kiếm tùy chỉnh
        $('#customSearch').on('keyup', function() {
            table.search(this.value).draw();
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- /* Hiển thị thông báo thành công sau khi thêm mới người dùng */ -->

    <?php if (isset($_SESSION['success'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '<?php echo addslashes($_SESSION['success']); ?>',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
        });
    });
    </script>
    <?php unset($_SESSION['success']); endif; ?>
    <!-- /* Hiển thị thông báo thành công sau khi xóa người dùng */ -->

    <script>
    $(document).ready(function() {
        $('.btn-delete-user').on('click', function(e) {
            e.preventDefault();
            var userId = $(this).data('id');
            Swal.fire({
                title: "Bạn có chắc chắn?",
                text: "Bạn sẽ không thể khôi phục lại tài khoản này!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Vâng, xóa nó!"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "delete-user.php?id=" + userId;
                }
            });
        });
    });
    </script>
</body>

</html>