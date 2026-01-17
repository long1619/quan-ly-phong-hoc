<?php
    session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../auth/login.php');
        exit;
    }
    include __DIR__ . '/../common/header.php';
    require_once __DIR__ . '/../../config/connect.php';
    require_once __DIR__ . '/../../helpers/helpers.php';
    $userRole = $_SESSION['user_role'] ?? '';

    // Kiểm tra quyền đặt phòng
    if (!checkPermission($conn, $userRole, 'create_booking')) {
        echo "<script>alert('Bạn không có quyền đặt phòng!'); window.location.href='../dashboard/index.php';</script>";
        exit;
    }

    // Lấy lỗi và dữ liệu cũ nếu có
    $errors = $_SESSION['errors'] ?? [];
    $old = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);

    // Lấy room_id từ URL nếu có
    $selectedRoomId = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

    // Kiểm tra nếu room_id từ URL có hợp lệ (active và không bảo trì)
    if ($selectedRoomId > 0) {
        $checkStmt = $conn->prepare("SELECT id, room_code, status, is_active FROM rooms WHERE id = ?");
        $checkStmt->bind_param("i", $selectedRoomId);
        $checkStmt->execute();
        $roomCheck = $checkStmt->get_result()->fetch_assoc();

        if (!$roomCheck || $roomCheck['is_active'] != 1 || $roomCheck['status'] == 'bao_tri') {
            $_SESSION['errors'] = ['Phòng không khả dụng để đặt (có thể đã bị vô hiệu hóa hoặc đang bảo trì).'];
            header('Location: ../room/list-room.php');
            exit;
        }
        $checkStmt->close();
    }

    // Lấy danh sách phòng từ database (chỉ lấy phòng đang hoạt động và không phải đang bảo trì)
    $sql = "SELECT id, room_code, room_name, capacity FROM rooms WHERE is_active = 1 AND status != 'bao_tri' ORDER BY room_code ASC";
    $rooms = $conn->query($sql);
?>
<style>
/* Steps */
.steps-container {
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 24px 20px;
    background: #fafafa;
    position: relative;
    margin-bottom: 30px;
    border-radius: 8px;
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

/* Form sections */
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

.time-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.time-input-group {
    position: relative;
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

/* Footer buttons */
.card-footer {
    padding: 20px 30px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #fafafa;
}

.btn-secondary {
    background: white;
    color: #333;
    border: 1px solid #ddd;
    margin-right: auto;
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
    border: none;
}

.btn-primary:hover {
    background: #1d4ed8;
}

/* Responsive */
@media (max-width: 600px) {
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
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include __DIR__ . '/../common/menu-sidebar.php'; ?>
            <div class="layout-page">
                <?php include __DIR__ . '/../common/navbar.php'; ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="mb-1">Đặt phòng mới</h4>
                            </div>
                        </div>

                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <div class="card mb-4">
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

                            <!-- <div class="card-body"> -->
                            <div class="card-body mx-auto" style="width:70%">

                                <form id="bookingForm" method="POST" action="process-booking.php">

                                    <!-- ===== STEP 1: Chọn Phòng ===== -->
                                    <div class="form-section active" id="form-step-1">
                                        <!-- Chọn Phòng -->
                                        <div class="mb-3">
                                            <label class="form-label">Chọn phòng <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" id="roomSelect" name="room_id">
                                                <option value="">-- Chọn phòng --</option>
                                                <?php while ($room = $rooms->fetch_assoc()): ?>
                                                <option value="<?= $room['id'] ?>" data-capacity="<?= $room['capacity'] ?>" <?= ($selectedRoomId == $room['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($room['room_code']) ?> -
                                                    <?= htmlspecialchars($room['room_name']) ?>
                                                    (Sức chứa: <?= $room['capacity'] ?>)
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>

                                        <!-- Ngày sử dụng -->
                                        <div class="mb-3">
                                            <label class="form-label">Ngày sử dụng <span
                                                    class="text-danger">*</span></label>
                                            <div style="position: relative;">
                                                <input type="date" class="form-control" id="bookingDate"
                                                    name="booking_date" />
                                                <!-- <i class="bx bx-calendar time-input-icon"></i> -->
                                            </div>
                                        </div>

                                        <!-- Giờ -->
                                        <div class="mb-3">
                                            <label class="form-label">Giờ <span class="text-danger">*</span></label>
                                            <div class="time-row">
                                                <div class="time-input-group">
                                                    <input type="time" class="form-control" id="startTime"
                                                        name="start_time" min="07:00" max="21:00" />
                                                    <!-- <i class="bx bx-time time-input-icon"></i> -->
                                                </div>
                                                <div class="time-input-group">
                                                    <input type="time" class="form-control" id="endTime"
                                                        name="end_time" min="07:00" max="21:00" />
                                                    <!-- <i class="bx bx-time time-input-icon"></i> -->
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
                                        <div class="mb-3">
                                            <label class="form-label">Mục đích sử dụng <span
                                                    class="text-danger">*</span></label>
                                            <textarea class="form-control" id="purpose" name="purpose"
                                                placeholder="Ví dụ: Họp CLB Lập trình hàng tuần" rows="3"></textarea>
                                        </div>

                                        <!-- Số người tham gia -->
                                        <div class="mb-3">
                                            <label class="form-label">Số người tham gia dự kiến <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="attendees" name="attendees"
                                                value="" min="1" />
                                        </div>

                                        <!-- Số điện thoại -->
                                        <div class="mb-3">
                                            <label class="form-label">Số điện thoại liên hệ <span
                                                    class="text-danger">*</span></label>
                                            <input type="tel" class="form-control" id="phone" name="phone"
                                                placeholder="0912345678" />
                                        </div>

                                        <!-- Ghi chú thêm -->
                                        <div class="mb-3">
                                            <label class="form-label">Ghi chú thêm</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                                placeholder="Cần máy chiếu và micro..."></textarea>
                                        </div>
                                    </div>

                                    <!-- ===== STEP 3: Xác Nhận ===== -->
                                    <div class="form-section" id="form-step-3">
                                        <!-- Info Box -->
                                        <div class="info-box">
                                            <div class="info-box-title">Thông tin đặt phòng</div>
                                            <div class="info-row">
                                                <span class="info-label">Phòng:</span>
                                                <span class="info-value" id="confirm-room">--</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Ngày:</span>
                                                <span class="info-value" id="confirm-date">--/--/--</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Thời gian:</span>
                                                <span class="info-value" id="confirm-time">--</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Mục đích:</span>
                                                <span class="info-value" id="confirm-purpose">--</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Số người:</span>
                                                <span class="info-value" id="confirm-attendees">--</span>
                                            </div>
                                        </div>

                                        <!-- Checkboxes -->
                                        <div class="checkbox-group">
                                            <div class="checkbox-item">
                                                <input type="checkbox" id="confirm-checkbox-1" />
                                                <label for="confirm-checkbox-1">Tôi cam kết sử dụng đúng mục
                                                    đích</label>
                                            </div>
                                            <div class="checkbox-item">
                                                <input type="checkbox" id="confirm-checkbox-2" />
                                                <label for="confirm-checkbox-2">Tôi sẽ giữ gìn vệ sinh và tài
                                                    sản</label>
                                            </div>
                                        </div>
                                    </div>

                                </form>
                            </div>

                            <!-- Footer -->
                            <div class="card-footer">
                                <button class="btn btn-secondary" id="btn-back" onclick="handleBack()"
                                    style="display: none;">
                                    ← Quay lại
                                </button>
                                <button class="btn btn-primary justify-content-end" id="btn-next" onclick="handleNext()">
                                    Tiếp tục →
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php include __DIR__ . '/../common/footer.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../assets/vendor/libs/popper/popper.js"></script>
    <script src="../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../../assets/vendor/js/menu.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    let currentStep = 1;
    const totalSteps = 3;

    function updateStepUI() {
        // Update step indicators
        for (let i = 1; i <= totalSteps; i++) {
            const stepEl = document.getElementById(`step-${i}`);
            const formSection = document.getElementById(`form-step-${i}`);

            if (i < currentStep) {
                stepEl.classList.add('completed');
                stepEl.classList.remove('active');
                formSection.classList.remove('active');
            } else if (i === currentStep) {
                stepEl.classList.add('active');
                stepEl.classList.remove('completed');
                formSection.classList.add('active');
            } else {
                stepEl.classList.remove('active', 'completed');
                formSection.classList.remove('active');
            }
        }

        // Update connector lines
        document.getElementById('connector-1').classList.toggle('active', currentStep > 1);
        document.getElementById('connector-2').classList.toggle('active', currentStep > 2);

        // Update buttons
        const btnBack = document.getElementById('btn-back');
        const btnNext = document.getElementById('btn-next');

        btnBack.style.display = currentStep > 1 ? 'inline-flex' : 'none';

        if (currentStep === totalSteps) {
            btnNext.innerHTML = 'Xác nhận đặt phòng';
        } else {
            btnNext.innerHTML = 'Tiếp tục →';
        }
    }

    function validateStep(step) {
        if (step === 1) {
            const room = document.getElementById('roomSelect').value;
            const date = document.getElementById('bookingDate').value;
            const startTime = document.getElementById('startTime').value;
            const endTime = document.getElementById('endTime').value;

            if (!room || !date || !startTime || !endTime) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Thiếu thông tin',
                    text: 'Vui lòng điền đầy đủ thông tin bước 1'
                });
                return false;
            }

            if (startTime >= endTime) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Thời gian không hợp lệ',
                    text: 'Giờ kết thúc phải sau giờ bắt đầu'
                });
                return false;
            }

            if (startTime < "07:00" || endTime > "21:00") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Thời gian không hợp lệ',
                    text: 'Chỉ cho phép đặt phòng từ 07:00 sáng đến 21:00 tối'
                });
                return false;
            }
        } else if (step === 2) {
            const purpose = document.getElementById('purpose').value;
            const attendees = document.getElementById('attendees').value;
            const phone = document.getElementById('phone').value;

            if (!purpose || !attendees || !phone) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Thiếu thông tin',
                    text: 'Vui lòng điền đầy đủ thông tin bước 2'
                });
                return false;
            }

            // Validate capacity
            const roomSelect = document.getElementById('roomSelect');
            const selectedOption = roomSelect.options[roomSelect.selectedIndex];
            const capacity = parseInt(selectedOption.getAttribute('data-capacity') || 0);
            if (parseInt(attendees) > capacity) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Vượt quá sức chứa',
                    text: `Số người (${attendees}) vượt quá sức chứa của phòng (${capacity} người).`
                });
                return false;
            }
        }
        return true;
    }

    function updateConfirmation() {
        // Get room text
        const roomSelect = document.getElementById('roomSelect');
        const roomText = roomSelect.options[roomSelect.selectedIndex].text;
        document.getElementById('confirm-room').textContent = roomText;

        // Get date
        const date = document.getElementById('bookingDate').value;
        if (date) {
            const dateObj = new Date(date);
            const formattedDate = dateObj.toLocaleDateString('vi-VN');
            document.getElementById('confirm-date').textContent = formattedDate;
        }

        // Get time
        const startTime = document.getElementById('startTime').value;
        const endTime = document.getElementById('endTime').value;
        document.getElementById('confirm-time').textContent = `${startTime} - ${endTime}`;

        // Get purpose
        const purpose = document.getElementById('purpose').value;
        document.getElementById('confirm-purpose').textContent = purpose || '--';

        // Get attendees
        const attendees = document.getElementById('attendees').value;
        document.getElementById('confirm-attendees').textContent = attendees + ' người';
    }

    async function handleNext() {
        if (!validateStep(currentStep)) {
            return;
        }

        if (currentStep === 1) {
            const room = document.getElementById('roomSelect').value;
            const date = document.getElementById('bookingDate').value;
            const startTime = document.getElementById('startTime').value;
            const endTime = document.getElementById('endTime').value;

            try {
                const response = await fetch(`check-room-availability.php?room_id=${room}&date=${date}&start_time=${startTime}&end_time=${endTime}`);
                const data = await response.json();
                if (!data.available) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Phòng đã bận',
                        text: data.message
                    });
                    return;
                }
            } catch (error) {
                console.error('Lỗi kiểm tra phòng:', error);
            }
        }

        if (currentStep < totalSteps) {
            currentStep++;
            if (currentStep === 3) {
                updateConfirmation();
            }
            updateStepUI();
        } else {
            // Submit form
            const checkbox1 = document.getElementById('confirm-checkbox-1').checked;
            const checkbox2 = document.getElementById('confirm-checkbox-2').checked;

            if (!checkbox1 || !checkbox2) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Chưa xác nhận',
                    text: 'Vui lòng xác nhận các cam kết'
                });
                return;
            }

            document.getElementById('bookingForm').submit();
        }
    }

    function handleBack() {
        if (currentStep > 1) {
            currentStep--;
            updateStepUI();
        }
    }

    // Initialize
    updateStepUI();

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('bookingDate').setAttribute('min', today);
    </script>

    <?php if (isset($_SESSION['success'])): ?>
    <script>
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: '<?php echo addslashes($_SESSION['success']); ?>',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
    });
    </script>
    <?php unset($_SESSION['success']); endif; ?>
</body>

</html>