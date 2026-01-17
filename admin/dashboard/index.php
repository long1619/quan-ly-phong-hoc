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
    // Yêu cầu: "Thay Phòng trống bằng tổng user có trong hệ thống trừ role admin và user"
    // Giả định: Trừ role 'admin'. Nếu có role 'user' thì cũng trừ. Ở đây ta trừ 'admin'.
    $totalUsers = 0;
    $sqlUsers = "SELECT COUNT(*) as cnt FROM users WHERE role != 'admin'";
    $resUsers = $conn->query($sqlUsers);
    if ($resUsers && $row = $resUsers->fetch_assoc()) {
        $totalUsers = $row['cnt'];
    }

    // --- 3. Tổng đơn đã duyệt (Thay cho Tỷ lệ sử dụng) ---
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
        $dayName = date('D', strtotime($date)); // Mon, Tue...
        // Map sang tiếng Việt T2, T3...
        $dayMap = [
            'Mon' => 'T2', 'Tue' => 'T3', 'Wed' => 'T4', 'Thu' => 'T5',
            'Fri' => 'T6', 'Sat' => 'T7', 'Sun' => 'CN'
        ];
        $chart1Labels[] = isset($dayMap[$dayName]) ? $dayMap[$dayName] : $dayName;

        // Query count for this date
        $sqlDay = "SELECT COUNT(*) as cnt FROM bookings WHERE booking_date = '$date' AND status != 'da_huy'";
        $resDay = $conn->query($sqlDay);
        $countDay = 0;
        if ($resDay && $r = $resDay->fetch_assoc()) {
            $countDay = $r['cnt'];
        }
        $chart1Data[] = $countDay;
    }

    // --- 6. Chart 2: Phân bổ loại phòng (Dựa trên số lượng booking của các loại phòng khác nhau) ---
    $chart2Labels = [];
    $chart2Data = [];
    $sqlType = "SELECT rt.type_name, COUNT(r.id) as cnt
                FROM room_types rt
                LEFT JOIN rooms r ON r.type_id = rt.id
                WHERE r.is_active = 1
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
            // Map status text & color
            $statusText = 'Chờ duyệt';
            $colorClass = 'orange'; // default css class from template
            if ($row['status'] == 'da_duyet') {
                $statusText = 'Đã duyệt';
                $colorClass = 'blue';
                // Logic giả định màu sắc
                if (stripos($row['room_name'], 'Lab') !== false) {
                     $colorClass = 'green';
                }
            } else {
                $colorClass = 'orange';
            }

            $todaySchedules[] = [
                'time' => date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])),
                'room' => $row['room_code'] . ' - ' . $row['room_name'],
                'title' => $row['purpose'],
                'status' => $statusText,
                'color' => $colorClass
            ];
        }
    }
?>
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
          <div>
            <h4 class="mb-1">Dashboard</h4>
            <p class="text-muted">Xin chào, <?php echo htmlspecialchars($userLoginId['full_name']); ?></p>
          </div>

          <!-- Statistics Cards -->
          <div class="row mb-4">
            <div class="col-lg-3 col-md-6 col-6 mb-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <span class="d-block text-muted small mb-2">Tổng số phòng</span>
                      <h3 class="mb-0"><?php echo $totalRooms; ?></h3>
                    </div>
                    <div class="avatar">
                      <span class="avatar-initial rounded bg-label-primary">
                        <i class="bx bx-home"></i>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 col-6 mb-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <span class="d-block text-muted small mb-2">Tổng người dùng</span>
                      <h3 class="mb-0"><?php echo $totalUsers; ?></h3>
                    </div>
                    <div class="avatar">
                      <span class="avatar-initial rounded bg-label-success">
                        <i class="bx bx-user"></i>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 col-6 mb-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <span class="d-block text-muted small mb-2">Đơn đã duyệt</span>
                      <h3 class="mb-0"><?php echo $approvedBookings; ?></h3>
                    </div>
                    <div class="avatar">
                      <span class="avatar-initial rounded bg-label-warning">
                        <i class="bx bx-check-circle"></i>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 col-6 mb-4">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <span class="d-block text-muted small mb-2">Chờ duyệt</span>
                      <h3 class="mb-0"><?php echo $pendingCount; ?></h3>
                    </div>
                    <div class="avatar">
                      <span class="avatar-initial rounded bg-label-danger">
                        <i class="bx bx-time-five"></i>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Charts Row -->
          <div class="row mb-4">
            <!-- Bar Chart -->
            <div class="col-lg-6 mb-4">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">Lượt đặt theo ngày (7 ngày qua)</h5>
                </div>
                <div class="card-body">
                  <div class="chart-container" style="height:300px;">
                    <canvas id="bookingByDayChart"></canvas>
                  </div>
                </div>
              </div>
            </div>
            <!-- Doughnut Chart -->
            <div class="col-lg-6 mb-4">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">Phân bổ loại phòng</h5>
                </div>
                <div class="card-body">
                  <div class="doughnut-container" style="height:300px;">
                    <canvas id="roomTypeChart"></canvas>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Today's Schedule -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="card-title mb-0">Lịch hôm nay</h5>
                  <a href="../booking/booking-room.php" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus"></i>
                    Đặt phòng mới
                  </a>
                </div>
                <div class="card-body">
                  <div class="schedule-list" id="todaySchedule">
                    <!-- Schedule items will be generated here -->
                    <?php if (empty($todaySchedules)): ?>
                        <p class="text-center text-muted">Hôm nay không có lịch đặt phòng nào.</p>
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
<!-- build:js assets/vendor/js/core.js -->
<script src="../../assets/vendor/libs/jquery/jquery.js"></script>
<script src="../../assets/vendor/libs/popper/popper.js"></script>
<script src="../../assets/vendor/js/bootstrap.js"></script>
<script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="../../assets/vendor/js/menu.js"></script>
<!-- endbuild -->

<!-- Main JS -->
<script src="../../assets/js/main.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  let barChart = null;
  let doughnutChart = null;

  // Data from PHP
  const chart1Labels = <?php echo json_encode($chart1Labels); ?>;
  const chart1Data = <?php echo json_encode($chart1Data); ?>;

  const chart2Labels = <?php echo json_encode($chart2Labels); ?>;
  const chart2Data = <?php echo json_encode($chart2Data); ?>;

  const todaySchedules = <?php echo json_encode($todaySchedules); ?>;

  document.addEventListener('DOMContentLoaded', function () {
    initBarChart();
    initDoughnutChart();
    renderTodaySchedule();
  });

  function initBarChart() {
    const ctx = document.getElementById('bookingByDayChart');
    if (!ctx) return;

    if (barChart) {
      barChart.destroy();
    }

    barChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: chart1Labels,
        datasets: [
          {
            label: 'Lượt đặt',
            data: chart1Data,
            backgroundColor: '#2563eb',
            borderColor: '#2563eb',
            borderWidth: 0,
            borderRadius: 6,
            borderSkipped: false,
            barPercentage: 0.6,
            categoryPercentage: 0.7,
            fill: true
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'x',
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleFont: {
              size: 14,
              weight: 'bold'
            },
            bodyFont: {
              size: 13
            },
            borderColor: '#ddd',
            borderWidth: 1,
            displayColors: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
              font: {
                size: 12,
                weight: '500'
              },
              color: '#999'
            },
            grid: {
              color: '#e8e8e8',
              drawBorder: false,
              drawTicks: false
            }
          },
          x: {
            ticks: {
              font: {
                size: 12,
                weight: '500'
              },
              color: '#666'
            },
            grid: {
              display: false,
              drawBorder: false
            }
          }
        }
      }
    });
  }

  function initDoughnutChart() {
    const ctx = document.getElementById('roomTypeChart');
    if (!ctx) return;

    if (doughnutChart) {
      doughnutChart.destroy();
    }

    doughnutChart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: chart2Labels,
        datasets: [
          {
            data: chart2Data,
            backgroundColor: [
              '#2563eb',
              '#ff9800',
              '#ef5350',
              '#10b981',
              '#8b5cf6',
              '#ec4899'
            ],
            borderColor: '#fff',
            borderWidth: 2,
            hoverBorderWidth: 3,
            offset: 3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              font: {
                size: 12,
                weight: '500'
              },
              color: '#666',
              padding: 15,
              usePointStyle: true,
              pointStyle: 'circle'
            }
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleFont: {
              size: 13,
              weight: 'bold'
            },
            bodyFont: {
              size: 12
            },
            borderColor: '#ddd',
            borderWidth: 1,
            displayColors: true,
            callbacks: {
              label: function (context) {
                // Hiển thị số lượng
                let val = context.parsed;
                return context.label + ': ' + val;
              }
            }
          }
        }
      }
    });
  }

  function renderTodaySchedule() {
    const schedules = todaySchedules;
    const scheduleContainer = document.getElementById('todaySchedule');

    if (schedules.length === 0) {
        // Handled in PHP for simple message, but for safety:
        if (scheduleContainer.children.length === 0) {
            scheduleContainer.innerHTML = '<p class="text-center text-muted">Không có lịch nào.</p>';
        }
        return;
    }

    scheduleContainer.innerHTML = schedules.map(schedule => `
      <div class="schedule-item ${schedule.color === 'orange' ? 'orange' : schedule.color === 'green' ? 'green' : ''}">
        <div class="schedule-item-content">
          <div class="schedule-item-time">
            <i class="bx bx-time-five"></i>
            ${schedule.time}
          </div>
          <div class="schedule-item-location">
            <i class="bx bx-map-pin"></i>
            ${schedule.room}
          </div>
          <div class="schedule-item-title">${schedule.title}</div>
        </div>
        <div class="schedule-item-status">${schedule.status}</div>
      </div>
    `).join('');
  }
</script>
<style>
.schedule-item {
    display: flex;
    align-items: center;
    padding: 16px;
    border-left: 4px solid #2563eb;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 12px;
}
.schedule-item.orange { border-left-color: #ff9800; }
.schedule-item.green { border-left-color: #10b981; }
.schedule-item-content { flex: 1; }
.schedule-item-time {
    display: flex; align-items: center; gap: 4px;
    font-size: 13px; font-weight: 600; color: #333; margin-bottom: 4px;
}
.schedule-item-location {
    display: flex; align-items: center; gap: 4px;
    font-size: 13px; color: #999; margin-bottom: 4px;
}
.schedule-item-title { font-size: 13px; color: #666; }
.schedule-item-status {
    background: #e8f5e9; color: #2e7d32;
    padding: 4px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600; white-space: nowrap;
}
</style>