<?php
    session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../auth/login.php');
        exit;
    }
    include __DIR__ . '/../common/header.php';
    require_once __DIR__ . '/../../config/connect.php';

    $idLogin = $_SESSION['user_id'];

    // Lấy danh sách phòng từ database
    $rooms_sql = "SELECT id, room_code, room_name, building, floor, capacity, status, facilities FROM rooms WHERE is_active = 1 ORDER BY room_code ASC";
    $rooms_result = $conn->query($rooms_sql);
    $roomsList = [];
    $roomsInfo = [];
    if ($rooms_result && $rooms_result->num_rows > 0) {
        while ($room = $rooms_result->fetch_assoc()) {
            $roomsList[] = $room;
            $roomsInfo[$room['id']] = $room;
        }
    }

    // Lấy room_id từ URL nếu có
    $selectedRoomId = isset($_GET['id']) ? intval($_GET['id']) : (count($roomsList) > 0 ? $roomsList[0]['id'] : 0);

    // Lấy tất cả bookings từ database
    $bookings_sql = "
        SELECT
            b.id,
            b.booking_code,
            b.room_id,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.purpose,
            b.participants,
            b.status,
            u.full_name as user_name
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.status IN ('cho_duyet', 'da_duyet')
        ORDER BY b.booking_date, b.start_time
    ";
    $bookings_result = $conn->query($bookings_sql);
    $bookingsData = [];
    if ($bookings_result && $bookings_result->num_rows > 0) {
        while ($booking = $bookings_result->fetch_assoc()) {
            $roomId = $booking['room_id'];
            if (!isset($bookingsData[$roomId])) {
                $bookingsData[$roomId] = [];
            }
            $bookingsData[$roomId][] = $booking;
        }
    }

    // Chuyển dữ liệu PHP sang JavaScript
    $roomsJSON = json_encode($roomsInfo);
    $bookingsJSON = json_encode($bookingsData);
?>
<style>
.schedule-container {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.header-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    gap: 20px;
    flex-wrap: wrap;
}

.header-left h4 {
    font-weight: 700;
    margin: 0;
}

.controls-right {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.room-selector {
    min-width: 200px;
}

.room-selector select {
    border-radius: 6px;
    border: 1px solid #ddd;
    padding: 8px 12px;
    font-size: 14px;
}

.date-navigator {
    display: flex;
    gap: 10px;
    align-items: center;
}

.date-navigator button {
    padding: 8px 16px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.date-navigator button:hover {
    background: #f5f5f5;
    border-color: #999;
}

.current-date {
    min-width: 180px;
    text-align: center;
    font-weight: 600;
    color: #333;
}

.view-toggle {
    display: flex;
    gap: 5px;
}

.view-toggle button {
    padding: 8px 12px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}

.view-toggle button.active {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

/* Calendar */
.calendar-widget {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    width: 280px;
    position: sticky;
    top: 20px;
    height: fit-content;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.calendar-header h5 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.calendar-header button {
    background: none;
    border: none;
    padding: 4px 8px;
    cursor: pointer;
    color: #2563eb;
}

.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
    margin-bottom: 10px;
}

.calendar-weekday {
    text-align: center;
    font-size: 12px;
    font-weight: 600;
    color: #999;
    padding: 8px 0;
}

.calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
}

.calendar-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}

.calendar-day:hover {
    border-color: #2563eb;
    background: #f0f6ff;
}

.calendar-day.selected {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

.calendar-day.today {
    background: #fff3cd;
    border-color: #ffc107;
}

.calendar-day.disabled {
    color: #ccc;
    cursor: not-allowed;
}

/* Ngày có đặt phòng */
.calendar-day.has-booking {
    position: relative;
}

.calendar-day.has-booking::after {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #2563eb;
}

.calendar-day.has-pending::after {
    background: #ffa726;
}

.calendar-day.has-approved::after {
    background: #10b981;
}

/* Schedule Grid */
.schedule-grid {
    overflow-x: auto;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: white;
}

.schedule-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.schedule-table th {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 12px 8px;
    text-align: center;
    font-weight: 600;
    font-size: 13px;
    color: #333;
}

.schedule-table td {
    border: 1px solid #ddd;
    padding: 8px;
    height: 60px;
    vertical-align: middle;
    text-align: center;
    position: relative;
}

.time-slot {
    font-size: 12px;
    color: #666;
    font-weight: 500;
}

.booking-slot {
    position: absolute;
    top: 2px;
    left: 2px;
    right: 2px;
    bottom: 2px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    color: white;
    transition: all 0.2s;
    padding: 4px;
    text-align: center;
    line-height: 1.3;
}

.booking-slot:hover {
    opacity: 0.9;
    transform: scale(1.02);
}

.booking-slot.da_duyet {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.booking-slot.cho_duyet {
    background: linear-gradient(135deg, #ffa726 0%, #f57c00 100%);
    color: #fff;
    box-shadow: 0 2px 8px rgba(255, 167, 38, 0.3);
}

.booking-slot.available {
    background: #e8f5e9;
    color: #2e7d32;
    border: 2px dashed #4caf50;
    cursor: pointer;
}

.booking-slot.available:hover {
    background: #c8e6c9;
}

.room-info-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    width: 280px;
    position: sticky;
    top: 20px;
    height: fit-content;
}

.room-info-card h5 {
    font-weight: 600;
    margin-bottom: 15px;
    color: #333;
}

.room-info-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
}

.room-info-item:last-child {
    border-bottom: none;
}

.room-info-label {
    color: #666;
    font-weight: 500;
}

.room-info-value {
    color: #333;
    font-weight: 600;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 10px;
}

.status-available {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-busy {
    background: #ffebee;
    color: #c62828;
}

.btn-book-time {
    width: 100%;
    background: #2563eb;
    color: white;
    border: none;
    padding: 10px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 15px;
    transition: all 0.2s;
}

.btn-book-time:hover {
    background: #1d4ed8;
}

.legend {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.legend-color {
    width: 24px;
    height: 24px;
    border-radius: 4px;
}

.legend-color.da_duyet {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.legend-color.available {
    background: #e8f5e9;
    border: 2px dashed #4caf50;
}

.legend-color.cho_duyet {
    background: linear-gradient(135deg, #ffa726 0%, #f57c00 100%);
}

@media (max-width: 1024px) {
    .schedule-layout {
        flex-direction: column;
    }

    .calendar-widget,
    .room-info-card {
        width: 100%;
        position: static;
    }
}

.schedule-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 20px;
}

.schedule-main {
    display: flex;
    flex-direction: column;
}

/* Booking detail tooltip */
.booking-tooltip {
    display: none;
    position: absolute;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 1000;
    min-width: 250px;
    text-align: left;
}

.booking-tooltip.show {
    display: block;
}

.booking-tooltip h6 {
    margin: 0 0 10px 0;
    font-size: 14px;
    font-weight: 700;
    color: #333;
}

.booking-tooltip p {
    margin: 5px 0;
    font-size: 12px;
    color: #666;
}

.booking-tooltip .status-tag {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
}

.booking-tooltip .status-tag.da_duyet {
    background: #e8f5e9;
    color: #2e7d32;
}

.booking-tooltip .status-tag.cho_duyet {
    background: #fff3cd;
    color: #856404;
}
</style>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <?php
      include __DIR__ . '/../common/menu-sidebar.php';
    ?>

            <!-- Layout container -->
            <div class="layout-page">
                <?php
        include __DIR__ . '/../common/navbar.php';
      ?>

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl grow container-p-y">
                        <!-- Header Controls -->
                        <div class="header-controls">
                            <div class="header-left">
                                <h4>Lịch phòng</h4>
                            </div>
                            <div class="controls-right">
                                <div class="room-selector">
                                    <select id="roomSelect" onchange="updateSchedule()">
                                        <?php foreach($roomsList as $room): ?>
                                        <option value="<?= $room['id'] ?>" <?= $room['id'] == $selectedRoomId ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($room['room_code']) ?> - <?= htmlspecialchars($room['room_name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="date-navigator">
                                    <button onclick="previousWeek()" title="Tuần trước">
                                        <i class="bx bx-chevron-left"></i>
                                    </button>
                                    <div class="current-date" id="currentDateDisplay"></div>
                                    <button onclick="nextWeek()" title="Tuần sau">
                                        <i class="bx bx-chevron-right"></i>
                                    </button>
                                </div>
                                <div class="view-toggle">
                                    <button class="active" onclick="setView('week')">Tuần</button>
                                    <button onclick="setView('day')">Ngày</button>
                                </div>
                                <a href="booking-room.php">
                                    <button
                                        style="background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                        + Đặt phòng
                                    </button>
                                </a>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="legend">
                            <div class="legend-item">
                                <div class="legend-color da_duyet"></div>
                                <span>Đã duyệt</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color available"></div>
                                <span>Phòng trống</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color cho_duyet"></div>
                                <span>Chờ duyệt</span>
                            </div>
                        </div>

                        <!-- Main Layout -->
                        <div class="schedule-layout">
                            <!-- Left Sidebar: Calendar -->
                            <div>
                                <div class="calendar-widget" id="calendarWidget"></div>
                                <div class="room-info-card">
                                    <h5>Thông tin phòng</h5>
                                    <div class="room-info-item">
                                        <span class="room-info-label">Phòng:</span>
                                        <span class="room-info-value" id="roomName">-</span>
                                    </div>
                                    <div class="room-info-item">
                                        <span class="room-info-label">Vị trí:</span>
                                        <span class="room-info-value" id="roomLocation">-</span>
                                    </div>
                                    <div class="room-info-item">
                                        <span class="room-info-label">Sức chứa:</span>
                                        <span class="room-info-value" id="roomCapacity">-</span>
                                    </div>
                                    <div class="room-info-item">
                                        <span class="room-info-label">Trạng thái hôm nay:</span>
                                        <span class="room-info-value" id="roomStatus">-</span>
                                    </div>
                                    <div>
                                        <div class="status-badge status-available" id="statusBadge">Có sẵn</div>
                                    </div>
                                    <button class="btn-book-time" onclick="openBookingModal()">Đặt phòng</button>
                                </div>
                            </div>

                            <!-- Right: Schedule Grid -->
                            <div class="schedule-main">
                                <div class="schedule-container">
                                    <div class="schedule-grid">
                                        <table class="schedule-table" id="scheduleTable">
                                            <!-- Generated by JavaScript -->
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    <?php
              include __DIR__ . '/../common/footer.php';
            ?>
                    <!-- / Footer -->
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout container -->
        </div>

        <!-- Overlay -->
    </div>
    <!-- / Layout wrapper -->

    <!-- Booking Tooltip -->
    <div class="booking-tooltip" id="bookingTooltip">
        <h6 id="tooltipTitle">-</h6>
        <p><strong>Người đặt:</strong> <span id="tooltipUser">-</span></p>
        <p><strong>Thời gian:</strong> <span id="tooltipTime">-</span></p>
        <p><strong>Mục đích:</strong> <span id="tooltipPurpose">-</span></p>
        <p><strong>Số người:</strong> <span id="tooltipParticipants">-</span></p>
        <p><strong>Trạng thái:</strong> <span id="tooltipStatus" class="status-tag">-</span></p>
    </div>

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

    <script>
    // Dữ liệu từ PHP
    const roomsInfo = <?= $roomsJSON ?>;
    const bookingsData = <?= $bookingsJSON ?>;

    let currentDate = new Date();
    let currentView = 'week';
    let selectedDate = new Date(currentDate);
    let selectedRoomId = <?= $selectedRoomId ?>;

    function initCalendar() {
        const calendar = document.getElementById('calendarWidget');
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = firstDay.getDay();

        // Lấy bookings của phòng hiện tại
        const roomBookings = bookingsData[selectedRoomId] || [];

        let html = `
          <div class="calendar-header">
            <h5>${new Date(year, month).toLocaleDateString('vi-VN', { month: 'long', year: 'numeric' })}</h5>
            <div>
              <button onclick="previousMonth()">‹</button>
              <button onclick="nextMonth()">›</button>
            </div>
          </div>
          <div class="calendar-weekdays">
            <div class="calendar-weekday">CN</div>
            <div class="calendar-weekday">T2</div>
            <div class="calendar-weekday">T3</div>
            <div class="calendar-weekday">T4</div>
            <div class="calendar-weekday">T5</div>
            <div class="calendar-weekday">T6</div>
            <div class="calendar-weekday">T7</div>
          </div>
          <div class="calendar-days">
        `;

        for (let i = 0; i < startingDayOfWeek; i++) {
            html += '<div class="calendar-day disabled"></div>';
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const dateStr = date.toISOString().split('T')[0];
            const isSelected = selectedDate.toDateString() === date.toDateString();
            const isToday = new Date().toDateString() === date.toDateString();

            // Kiểm tra xem ngày này có booking không
            let hasBookingClass = '';
            const dayBookings = roomBookings.filter(b => b.booking_date === dateStr);
            if (dayBookings.length > 0) {
                const hasApproved = dayBookings.some(b => b.status === 'da_duyet');
                const hasPending = dayBookings.some(b => b.status === 'cho_duyet');
                if (hasApproved) {
                    hasBookingClass = 'has-booking has-approved';
                } else if (hasPending) {
                    hasBookingClass = 'has-booking has-pending';
                }
            }

            const classes = `calendar-day ${isSelected ? 'selected' : ''} ${isToday ? 'today' : ''} ${hasBookingClass}`;
            html += `<div class="${classes}" onclick="selectDate(new Date(${year}, ${month}, ${day}))">${day}</div>`;
        }

        html += '</div>';
        calendar.innerHTML = html;
    }

    function initScheduleTable() {
        const table = document.getElementById('scheduleTable');
        if (!table) return;

        const roomId = document.getElementById('roomSelect').value;
        selectedRoomId = parseInt(roomId);
        const times = [];

        for (let hour = 7; hour <= 21; hour++) {
            times.push(`${String(hour).padStart(2, '0')}:00`);
        }

        let html = '<thead><tr><th style="width: 80px;">Giờ</th>';

        if (currentView === 'week') {
            for (let i = 0; i < 7; i++) {
                const date = new Date(currentDate);
                date.setDate(date.getDate() - date.getDay() + i);
                const dayName = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][i];
                const dateStr = date.toLocaleDateString('vi-VN', {
                    month: '2-digit',
                    day: '2-digit'
                });
                html += `<th>${dayName}<br><small>${dateStr}</small></th>`;
            }
        } else {
            html +=
                `<th>${selectedDate.toLocaleDateString('vi-VN', { weekday: 'long', month: 'long', day: 'numeric' })}</th>`;
        }

        html += '</tr></thead><tbody>';

        // Mảng theo dõi việc gộp ô (rowspan) cho từng cột
        const skipRows = currentView === 'week' ? Array(7).fill(0) : [0];

        times.forEach(time => {
            const currentHour = parseInt(time.split(':')[0]);
            html += `<tr><td class="time-slot">${time}</td>`;

            const numCols = currentView === 'week' ? 7 : 1;
            for (let i = 0; i < numCols; i++) {
                if (skipRows[i] > 0) {
                    skipRows[i]--;
                    continue; // Bỏ qua ô này vì đã bị gộp từ trên
                }

                const date = new Date(currentDate);
                if (currentView === 'week') {
                    date.setDate(date.getDate() - date.getDay() + i);
                } else {
                    // Cần tạo bản sao của selectedDate để không bị ảnh hưởng bởi setDate ở trên (trong trường hợp loop trước đó)
                    date.setTime(selectedDate.getTime());
                }

                const booking = findBooking(roomId, date, time);

                if (booking) {
                    const bookingStartHour = parseInt(booking.start_time.split(':')[0]);
                    const bookingEndHour = parseInt(booking.end_time.split(':')[0]);

                    // Chỉ vẽ booking nếu đây là giờ bắt đầu (hoặc giờ đầu tiên của lịch hiển thị là 7:00)
                    if (currentHour === bookingStartHour || (currentHour === 7 && bookingStartHour < 7)) {
                        let displayStartHour = Math.max(7, bookingStartHour);
                        let displayEndHour = Math.min(22, bookingEndHour); // 21:00 là slot cuối, nên kéo dài đến hết 21:59 nếu cần

                        let rowspan = displayEndHour - displayStartHour;
                        if (rowspan < 1) rowspan = 1;

                        skipRows[i] = rowspan - 1;
                        html += `<td rowspan="${rowspan}">${renderBookingSlot(booking, date, time)}</td>`;
                    } else {
                        // Nếu lỡ findBooking tìm thấy mà không phải giờ bắt đầu và không bị skipRows,
                        // nghĩa là có vấn đề về logic skip, trả về ô trống để tránh lệch bảng
                        html += `<td>${renderBookingSlot(null, date, time)}</td>`;
                    }
                } else {
                    html += `<td>${renderBookingSlot(null, date, time)}</td>`;
                }
            }

            html += '</tr>';
        });

        html += '</tbody>';
        table.innerHTML = html;
        updateDateDisplay();
        initCalendar();
    }

    function renderBookingSlot(booking, date, time) {
        if (booking) {
            const statusClass = booking.status;
            const statusText = booking.status === 'da_duyet' ? 'Đã duyệt' : 'Chờ duyệt';
            const title = booking.purpose || 'Đặt phòng';
            const shortTitle = title.length > 15 ? title.substring(0, 15) + '...' : title;

            return `<div class="booking-slot ${statusClass}"
                        onclick="showBookingDetail(${JSON.stringify(booking).replace(/"/g, '&quot;')})"
                        title="${title} - ${statusText}">
                        <span>${shortTitle}</span>
                        <small>${booking.start_time.substring(0,5)} - ${booking.end_time.substring(0,5)}</small>
                    </div>`;
        } else {
            const dateStr = date.toISOString().split('T')[0];
            return `<div class="booking-slot available"
                        onclick="bookTimeSlot('${dateStr}', '${time}')"
                        title="Phòng trống - Click để đặt"></div>`;
        }
    }

    function findBooking(roomId, date, time) {
        const roomBookings = bookingsData[roomId] || [];
        const dateStr = date.toISOString().split('T')[0];
        const timeNum = parseInt(time.replace(':00', ''));

        return roomBookings.find(b => {
            if (b.booking_date !== dateStr) return false;
            const startHour = parseInt(b.start_time.split(':')[0]);
            const endHour = parseInt(b.end_time.split(':')[0]);
            return timeNum >= startHour && timeNum < endHour;
        });
    }

    function updateDateDisplay() {
        const display = document.getElementById('currentDateDisplay');
        if (currentView === 'week') {
            const start = new Date(currentDate);
            start.setDate(start.getDate() - start.getDay());
            const end = new Date(start);
            end.setDate(end.getDate() + 6);
            display.textContent = `${start.toLocaleDateString('vi-VN')} - ${end.toLocaleDateString('vi-VN')}`;
        } else {
            display.textContent = selectedDate.toLocaleDateString('vi-VN');
        }
    }

    function updateRoomInfo() {
        const roomId = document.getElementById('roomSelect').value;
        const info = roomsInfo[roomId];

        if (info) {
            document.getElementById('roomName').textContent = info.room_code + ' - ' + info.room_name;
            const location = info.building + (info.floor ? ', Tầng ' + info.floor : '');
            document.getElementById('roomLocation').textContent = location;
            document.getElementById('roomCapacity').textContent = info.capacity + ' người';

            // Kiểm tra trạng thái hôm nay
            const today = new Date().toISOString().split('T')[0];
            const roomBookings = bookingsData[roomId] || [];
            const todayBookings = roomBookings.filter(b => b.booking_date === today);

            if (todayBookings.length > 0) {
                const hasApproved = todayBookings.some(b => b.status === 'da_duyet');
                const hasPending = todayBookings.some(b => b.status === 'cho_duyet');

                if (hasApproved) {
                    document.getElementById('roomStatus').textContent = 'Có lịch đã duyệt';
                    document.getElementById('statusBadge').textContent = 'Đang sử dụng';
                    document.getElementById('statusBadge').className = 'status-badge status-busy';
                } else if (hasPending) {
                    document.getElementById('roomStatus').textContent = 'Có lịch chờ duyệt';
                    document.getElementById('statusBadge').textContent = 'Chờ xác nhận';
                    document.getElementById('statusBadge').className = 'status-badge';
                    document.getElementById('statusBadge').style.background = '#fff3cd';
                    document.getElementById('statusBadge').style.color = '#856404';
                }
            } else {
                document.getElementById('roomStatus').textContent = 'Trống';
                document.getElementById('statusBadge').textContent = 'Có sẵn';
                document.getElementById('statusBadge').className = 'status-badge status-available';
            }
        }
    }

    function updateSchedule() {
        updateRoomInfo();
        initScheduleTable();
    }

    function selectDate(date) {
        selectedDate = new Date(date);
        initCalendar();
        if (currentView === 'day') {
            initScheduleTable();
        }
    }

    function previousMonth() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        initCalendar();
    }

    function nextMonth() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        initCalendar();
    }

    function previousWeek() {
        currentDate.setDate(currentDate.getDate() - 7);
        initScheduleTable();
    }

    function nextWeek() {
        currentDate.setDate(currentDate.getDate() + 7);
        initScheduleTable();
    }

    function setView(view) {
        currentView = view;
        document.querySelectorAll('.view-toggle button').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        initScheduleTable();
    }

    function bookTimeSlot(dateStr, time) {
        const roomId = document.getElementById('roomSelect').value;
        window.location.href = `booking-room.php?room_id=${roomId}&date=${dateStr}&time=${time}`;
    }

    function showBookingDetail(booking) {
        const tooltip = document.getElementById('bookingTooltip');
        document.getElementById('tooltipTitle').textContent = booking.booking_code;
        document.getElementById('tooltipUser').textContent = booking.user_name || 'N/A';
        document.getElementById('tooltipTime').textContent = `${booking.start_time.substring(0,5)} - ${booking.end_time.substring(0,5)}`;
        document.getElementById('tooltipPurpose').textContent = booking.purpose || 'N/A';
        document.getElementById('tooltipParticipants').textContent = booking.participants + ' người';

        const statusEl = document.getElementById('tooltipStatus');
        statusEl.textContent = booking.status === 'da_duyet' ? 'Đã duyệt' : 'Chờ duyệt';
        statusEl.className = 'status-tag ' + booking.status;

        // Hiển thị tooltip
        tooltip.classList.add('show');
        tooltip.style.position = 'fixed';
        tooltip.style.top = '50%';
        tooltip.style.left = '50%';
        tooltip.style.transform = 'translate(-50%, -50%)';

        // Ẩn khi click ngoài
        setTimeout(() => {
            document.addEventListener('click', hideTooltip);
        }, 100);
    }

    function hideTooltip(e) {
        const tooltip = document.getElementById('bookingTooltip');
        if (!tooltip.contains(e.target)) {
            tooltip.classList.remove('show');
            document.removeEventListener('click', hideTooltip);
        }
    }

    function openBookingModal() {
        const roomId = document.getElementById('roomSelect').value;
        window.location.href = `booking-room.php?room_id=${roomId}`;
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        initCalendar();
        initScheduleTable();
        updateRoomInfo();
    });
    </script>
</body>

</html>