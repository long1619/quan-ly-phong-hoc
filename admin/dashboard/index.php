<?php
    session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../../login.php');
        exit;
    }
    include __DIR__ . '/../common/header.php';
    require_once __DIR__ . '/../../config/connect.php';
    require_once __DIR__ . '/../../helpers/helpers.php';

    // Lấy user id từ SESSION
    $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    $userLoginId = getUserById($conn, $userId);

    // --- 1. Tổng số phòng ---
    $totalRooms = 0;
    $sqlTotal = "SELECT COUNT(*) as total FROM rooms WHERE is_active = 1";
    $resTotal = $conn->query($sqlTotal);
    if ($resTotal && $row = $resTotal->fetch_assoc()) {
        $totalRooms = $row['total'];
    }

    // --- 2. Tổng người dùng (trừ role admin) ---
    $totalUsers = 0;
    $sqlUsers = "SELECT COUNT(*) as cnt FROM users WHERE role != 'admin'";
    $resUsers = $conn->query($sqlUsers);
    if ($resUsers && $row = $resUsers->fetch_assoc()) {
        $totalUsers = $row['cnt'];
    }

    // --- 3. Tổng đơn đã duyệt ---
    $approvedBookings = 0;
    $sqlApproved = "SELECT COUNT(*) as cnt FROM bookings WHERE status = 'da_duyet'";
    $resApproved = $conn->query($sqlApproved);
    if ($resApproved && $row = $resApproved->fetch_assoc()) {
        $approvedBookings = $row['cnt'];
    }

    // --- 4. Chờ duyệt ---
    $pendingCount = 0;
    $sqlPending = "SELECT COUNT(*) as cnt FROM bookings WHERE status = 'cho_duyet'";
    $resPending = $conn->query($sqlPending);
    if ($resPending && $row = $resPending->fetch_assoc()) {
        $pendingCount = $row['cnt'];
    }

    // --- 5. Chart 1: Lượt đặt theo ngày (7 ngày gần nhất) ---
    $chart1Labels = [];
    $chart1Data = [];

    // Tạo mảng 7 ngày gần nhất
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayName = date('D', strtotime($date));
        $dayMap = [
            'Mon' => 'T2', 'Tue' => 'T3', 'Wed' => 'T4', 'Thu' => 'T5',
            'Fri' => 'T6', 'Sat' => 'T7', 'Sun' => 'CN'
        ];
        $chart1Labels[] = isset($dayMap[$dayName]) ? $dayMap[$dayName] : $dayName;

        $sqlDay = "SELECT COUNT(*) as cnt FROM bookings WHERE booking_date = '$date' AND status != 'da_huy'";
        $resDay = $conn->query($sqlDay);
        $countDay = 0;
        if ($resDay && $r = $resDay->fetch_assoc()) {
            $countDay = $r['cnt'];
        }
        $chart1Data[] = $countDay;
    }

    // --- 6. Chart 2: Phân bổ loại phòng (UPDATE: Show ALL room types) ---
    $chart2Labels = [];
    $chart2Data = [];
    // FIX: Di chuyển điều kiện 'is_active = 1' vào trong ON clause để LEFT JOIN lấy hết room_types
    $sqlType = "SELECT rt.type_name, COUNT(r.id) as cnt
                FROM room_types rt
                LEFT JOIN rooms r ON r.type_id = rt.id AND r.is_active = 1
                GROUP BY rt.id, rt.type_name";
    $resType = $conn->query($sqlType);
    if ($resType) {
        while ($row = $resType->fetch_assoc()) {
            $chart2Labels[] = $row['type_name'];
            $chart2Data[] = $row['cnt'];
        }
    }

    // --- 7. Lịch hôm nay (Today's Schedule) ---
    $todaySchedules = [];
    $sqlToday = "SELECT b.start_time, b.end_time, r.room_code, r.room_name, b.purpose, b.status
                 FROM bookings b
                 JOIN rooms r ON b.room_id = r.id
                 WHERE b.booking_date = CURDATE()
                 AND b.status IN ('da_duyet', 'cho_duyet')
                 ORDER BY b.start_time ASC
                 LIMIT 5";
    $resToday = $conn->query($sqlToday);
    if ($resToday) {
        while ($row = $resToday->fetch_assoc()) {
            $statusText = 'Chờ duyệt';
            $colorClass = 'warning';
            $badgeClass = 'bg-label-warning';

            if ($row['status'] == 'da_duyet') {
                $statusText = 'Đã duyệt';
                $colorClass = 'success';
                $badgeClass = 'bg-label-success';
            }

            $todaySchedules[] = [
                'time' => date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])),
                'room' => $row['room_code'] . ' - ' . $row['room_name'],
                'title' => $row['purpose'],
                'status' => $statusText,
                'badgeClass' => $badgeClass
            ];
        }
    }
?>
<style>
    :root {
        --primary-color: #696cff;
        --secondary-color: #8592a3;
        --success-color: #71dd37;
        --info-color: #03c3ec;
        --warning-color: #ffab00;
        --danger-color: #ff3e1d;
        --light-color: #fcfdfd;
        --dark-color: #233446;
    }

    .card-hover-effect {
        transition: all 0.3s ease-in-out;
        border: none;
        box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12);
    }
    .card-hover-effect:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(67, 89, 113, 0.2);
    }

    .stat-icon-box {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.5rem;
    }

    .welcome-banner {
        background: linear-gradient(135deg, #696cff 0%, #9b9dff 100%);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        position: relative;
        overflow: hidden;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(105, 108, 255, 0.4);
    }
    .welcome-banner h4 { color: white; font-weight: 700; margin-bottom: 0.5rem; }
    .welcome-banner p { color: rgba(255,255,255,0.9); margin-bottom: 0; }
    .welcome-decorative {
        position: absolute;
        right: 0;
        bottom: 0;
        opacity: 0.2;
        font-size: 10rem;
        line-height: 0;
        transform: translate(20%, 20%);
    }

    .schedule-item {
        display: flex;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #f1f2f6;
        transition: background 0.2s;
    }
    .schedule-item:last-child { border-bottom: none; }
    .schedule-item:hover { background-color: #f8f9fa; }

    .schedule-time-box {
        min-width: 100px;
        text-align: center;
        margin-right: 15px;
    }
    .schedule-time {
        font-weight: 700;
        color: #566a7f;
        display: block;
    }
    .schedule-content { flex: 1; }
    .schedule-room {
        font-size: 0.85rem;
        color: #697a8d;
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 2px;
    }
    .schedule-title {
        font-weight: 600;
        color: #566a7f;
        margin-bottom: 0;
    }
</style>

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

            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="d-flex align-items-center position-relative" style="z-index: 2;">
                    <div>
                        <h4>Xin chào, <?php echo htmlspecialchars($userLoginId['full_name']); ?>! 👋</h4>
                        <p>Chúc bạn một ngày làm việc hiệu quả. Dưới đây là tổng quan hệ thống hôm nay.</p>
                    </div>
                </div>
                <div class="welcome-decorative">
                    <i class='bx bx-building-house'></i>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 col-6 mb-4">
                    <div class="card card-hover-effect h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="d-block text-muted">Tổng phòng</span>
                                <div class="stat-icon-box bg-label-primary text-primary">
                                    <i class="bx bx-home-alt"></i>
                                </div>
                            </div>
                            <h3 class="mb-0 fw-bold"><?php echo $totalRooms; ?></h3>
                            <small class="text-success fw-semibold"><i class='bx bx-check-double'></i> Đang hoạt động</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-6 mb-4">
                    <div class="card card-hover-effect h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="d-block text-muted">Người dùng</span>
                                <div class="stat-icon-box bg-label-success text-success">
                                    <i class="bx bx-user-voice"></i>
                                </div>
                            </div>
                            <h3 class="mb-0 fw-bold"><?php echo $totalUsers; ?></h3>
                            <small class="text-muted">Không bao gồm Admin</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-6 mb-4">
                    <div class="card card-hover-effect h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="d-block text-muted">Đã duyệt</span>
                                <div class="stat-icon-box bg-label-warning text-warning">
                                    <i class="bx bx-task"></i>
                                </div>
                            </div>
                            <h3 class="mb-0 fw-bold"><?php echo $approvedBookings; ?></h3>
                            <small class="text-success fw-semibold">Tổng booking thành công</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-6 mb-4">
                    <div class="card card-hover-effect h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="d-block text-muted">Chờ xử lý</span>
                                <div class="stat-icon-box bg-label-danger text-danger">
                                    <i class="bx bx-time-five"></i>
                                </div>
                            </div>
                            <h3 class="mb-0 fw-bold"><?php echo $pendingCount; ?></h3>
                            <small class="text-danger fw-semibold">Cần duyệt ngay</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <!-- Bar Chart -->
                <div class="col-lg-8 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title m-0 me-2">Thống kê đặt phòng (7 ngày qua)</h5>
                            <div class="dropdown">
                                <button class="btn p-0" type="button" id="performanceOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div style="height: 350px;">
                                <canvas id="bookingByDayChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doughnut Chart -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title m-0 me-2">Phân bổ loại phòng</h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px; display: flex; align-items: center; justify-content: center;">
                                <canvas id="roomTypeChart"></canvas>
                            </div>
                            <div class="mt-3 text-center">
                                <small class="text-muted">Biểu đồ thể hiện số lượng phòng theo từng loại</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Schedule -->
            <div class="row">
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Lịch hoạt động hôm nay</h5>
                            <a href="../booking/booking-room.php" class="btn btn-primary btn-sm rounded-pill">
                                <i class="bx bx-plus"></i> Đặt phòng mới
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="schedule-list" id="todaySchedule">
                                <!-- JS will populate this -->
                                <?php if (empty($todaySchedules)): ?>
                                    <div class="p-4 text-center">
                                        <img src="../../assets/img/illustrations/girl-doing-yoga-light.png" alt="No Data" width="150" class="mb-3">
                                        <p class="text-muted">Hôm nay chưa có lịch đặt phòng nào.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
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
  <!-- Overlay -->
</div>
<!-- / Layout wrapper -->

<!-- Core JS -->
<script src="../../assets/vendor/libs/jquery/jquery.js"></script>
<script src="../../assets/vendor/libs/popper/popper.js"></script>
<script src="../../assets/vendor/js/bootstrap.js"></script>
<script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="../../assets/vendor/js/menu.js"></script>
<!-- Main JS -->
<script src="../../assets/js/main.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Data from PHP
  const chart1Labels = <?php echo json_encode($chart1Labels); ?>;
  const chart1Data = <?php echo json_encode($chart1Data); ?>;
  const chart2Labels = <?php echo json_encode($chart2Labels); ?>;
  const chart2Data = <?php echo json_encode($chart2Data); ?>;
  const todaySchedules = <?php echo json_encode($todaySchedules); ?>;

  document.addEventListener('DOMContentLoaded', function () {
    // 1. Bar Chart
    const barCtx = document.getElementById('bookingByDayChart');
    if (barCtx) {
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: chart1Labels,
                datasets: [{
                    label: 'Số lượt đặt',
                    data: chart1Data,
                    backgroundColor: 'rgba(105, 108, 255, 0.85)',
                    borderColor: 'rgba(105, 108, 255, 1)',
                    borderWidth: 0,
                    borderRadius: 5,
                    barPercentage: 0.5,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#fff',
                        titleColor: '#566a7f',
                        bodyColor: '#566a7f',
                        borderColor: '#e5e7eb',
                        borderWidth: 1,
                        padding: 10,
                        titleFont: { weight: 'bold' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#eceef1', borderDash: [5, 5] },
                        ticks: { stepSize: 1, color: '#a1acb8' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#a1acb8' }
                    }
                }
            }
        });
    }

    // 2. Doughnut Chart
    const doughCtx = document.getElementById('roomTypeChart');
    if (doughCtx) {
        new Chart(doughCtx, {
            type: 'doughnut',
            data: {
                labels: chart2Labels,
                datasets: [{
                    data: chart2Data,
                    backgroundColor: [
                        '#696cff', '#71dd37', '#03c3ec', '#ffab00', '#ff3e1d', '#8592a3'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            color: '#566a7f'
                        }
                    },
                    tooltip: {
                         backgroundColor: '#fff',
                         bodyColor: '#566a7f',
                         borderColor: '#e5e7eb',
                         borderWidth: 1
                    }
                },
                cutout: '70%'
            }
        });
    }

    // 3. Render Schedule
    const scheduleContainer = document.getElementById('todaySchedule');
    if (scheduleContainer && todaySchedules.length > 0) {
        scheduleContainer.innerHTML = todaySchedules.map(item => `
            <div class="schedule-item">
                <div class="schedule-time-box">
                    <span class="badge bg-label-primary mb-1">${item.time}</span>
                </div>
                <div class="schedule-content">
                    <div class="schedule-room">
                        <i class='bx bx-map'></i> ${item.room}
                    </div>
                    <p class="schedule-title">${item.title}</p>
                </div>
                <div class="schedule-status ms-3">
                    <span class="badge ${item.badgeClass}">${item.status}</span>
                </div>
            </div>
        `).join('');
    }
  });
</script>