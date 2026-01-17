<?php
/**
 * Lấy dữ liệu từ database để cung cấp context cho AI
 */

function getDatabaseContext($conn, $query) {
    $context = [];

    // Phân tích câu hỏi để quyết định query gì
    $queryLower = mb_strtolower($query, 'UTF-8');

    // 1. Thông tin về phòng
    if (containsKeywords($queryLower, ['phòng', 'room', 'trống', 'available', 'sức chứa', 'capacity'])) {
        $context['rooms'] = getRoomsData($conn, $queryLower);
        $context['room_types'] = getRoomTypes($conn);
    }

    // 2. Thông tin về đặt phòng
    if (containsKeywords($queryLower, ['đặt', 'booking', 'lịch', 'schedule', 'đơn', 'chờ duyệt', 'pending'])) {
        $context['bookings'] = getBookingsData($conn, $queryLower);
    }

    // 3. Thống kê tổng quan
    if (containsKeywords($queryLower, ['bao nhiêu', 'số lượng', 'thống kê', 'tổng', 'count'])) {
        $context['statistics'] = getStatistics($conn);
    }

    // 4. Người dùng
    if (containsKeywords($queryLower, ['người dùng', 'user', 'giảng viên', 'sinh viên'])) {
        $context['users'] = getUsersData($conn, $queryLower);
    }

    return $context;
}

/**
 * Kiểm tra câu hỏi có chứa từ khóa không
 */
function containsKeywords($text, $keywords) {
    foreach ($keywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Lấy thông tin phòng
 */
function getRoomsData($conn, $query) {
    $sql = "SELECT r.*, rt.type_name, rt.description as type_description
            FROM rooms r
            LEFT JOIN room_types rt ON r.type_id = rt.id
            WHERE 1=1";

    // Thêm điều kiện lọc dựa vào câu hỏi
    if (strpos($query, 'trống') !== false || strpos($query, 'available') !== false) {
        $sql .= " AND r.status = 'available'";
    }

    if (preg_match('/(\d+)\s*(người|person)/', $query, $matches)) {
        $capacity = intval($matches[1]);
        $sql .= " AND r.capacity >= $capacity";
    }

    $sql .= " ORDER BY r.room_code LIMIT 20";

    $result = mysqli_query($conn, $sql);
    $rooms = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rooms[] = [
            'room_code' => $row['room_code'],
            'room_name' => $row['room_name'],
            'capacity' => $row['capacity'],
            'status' => $row['status'],
            'type_name' => $row['type_name'],
            'location' => $row['location'] ?? 'N/A',
            'description' => $row['description'] ?? '',
            'status_text' => getStatusText($row['status'])
        ];
    }

    return $rooms;
}

/**
 * Lấy loại phòng
 */
function getRoomTypes($conn) {
    $sql = "SELECT * FROM room_types ORDER BY type_name";
    $result = mysqli_query($conn, $sql);
    $types = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $types[] = [
            'id' => $row['id'],
            'type_name' => $row['type_name'],
            'description' => $row['description'] ?? ''
        ];
    }

    return $types;
}

/**
 * Lấy thông tin đặt phòng
 */
function getBookingsData($conn, $query) {
    $sql = "SELECT b.*, r.room_code, r.room_name, u.fullname as user_name, u.email
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN users u ON b.user_id = u.id
            WHERE 1=1";

    // Lọc theo trạng thái
    if (strpos($query, 'chờ duyệt') !== false || strpos($query, 'pending') !== false) {
        $sql .= " AND b.status = 'pending'";
    } elseif (strpos($query, 'đã duyệt') !== false || strpos($query, 'approved') !== false) {
        $sql .= " AND b.status = 'approved'";
    }

    $sql .= " ORDER BY b.booking_date DESC, b.start_time DESC LIMIT 20";

    $result = mysqli_query($conn, $sql);
    $bookings = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $bookings[] = [
            'id' => $row['id'],
            'room_code' => $row['room_code'],
            'room_name' => $row['room_name'],
            'user_name' => $row['user_name'],
            'booking_date' => $row['booking_date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'purpose' => $row['purpose'],
            'status' => $row['status'],
            'status_text' => getBookingStatusText($row['status'])
        ];
    }

    return $bookings;
}

/**
 * Lấy thống kê
 */
function getStatistics($conn) {
    $stats = [];

    // Tổng số phòng
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM rooms");
    $stats['total_rooms'] = mysqli_fetch_assoc($result)['total'];

    // Phòng trống
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM rooms WHERE status = 'available'");
    $stats['available_rooms'] = mysqli_fetch_assoc($result)['total'];

    // Đơn chờ duyệt
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'");
    $stats['pending_bookings'] = mysqli_fetch_assoc($result)['total'];

    // Đơn đã duyệt
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE status = 'approved'");
    $stats['approved_bookings'] = mysqli_fetch_assoc($result)['total'];

    // Tổng người dùng
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = mysqli_fetch_assoc($result)['total'];

    // Phòng được đặt nhiều nhất
    $sql = "SELECT r.room_code, r.room_name, COUNT(b.id) as booking_count
            FROM rooms r
            LEFT JOIN bookings b ON r.id = b.room_id
            GROUP BY r.id
            ORDER BY booking_count DESC
            LIMIT 5";
    $result = mysqli_query($conn, $sql);
    $stats['most_booked_rooms'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['most_booked_rooms'][] = $row;
    }

    return $stats;
}

/**
 * Lấy thông tin người dùng
 */
function getUsersData($conn, $query) {
    $sql = "SELECT id, fullname, email, role, phone FROM users WHERE 1=1";

    if (strpos($query, 'giảng viên') !== false) {
        $sql .= " AND role = 'teacher'";
    } elseif (strpos($query, 'sinh viên') !== false) {
        $sql .= " AND role = 'student'";
    }

    $sql .= " ORDER BY fullname LIMIT 20";

    $result = mysqli_query($conn, $sql);
    $users = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = [
            'id' => $row['id'],
            'fullname' => $row['fullname'],
            'email' => $row['email'],
            'role' => $row['role'],
            'phone' => $row['phone'] ?? 'N/A'
        ];
    }

    return $users;
}

/**
 * Chuyển đổi status sang text
 */
function getStatusText($status) {
    $statusMap = [
        'available' => 'Còn trống',
        'occupied' => 'Đang sử dụng',
        'maintenance' => 'Bảo trì'
    ];
    return $statusMap[$status] ?? $status;
}

function getBookingStatusText($status) {
    $statusMap = [
        'pending' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Đã từ chối',
        'cancelled' => 'Đã hủy'
    ];
    return $statusMap[$status] ?? $status;
}