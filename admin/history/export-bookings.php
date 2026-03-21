<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
require_once __DIR__ . '/../../helpers/helpers.php';
$userRole = $_SESSION['user_role'] ?? '';

// Kiểm tra quyền
if (!checkPermission($conn, $userRole, 'view_history')) {
    die("Bạn không có quyền xem lịch sử!");
}

// Lấy bộ lọc
$status_filter = $_GET['status'] ?? 'all';
$type = $_GET['type'] ?? 'excel';

// Xây dựng điều kiện WHERE
$where_clause = "WHERE b.user_id = ?";
$params = [$user_id];
$types = "i";

if ($status_filter !== 'all') {
    $db_status = '';
    switch($status_filter) {
        case 'pending': $db_status = 'cho_duyet'; break;
        case 'approved': $db_status = 'da_duyet'; break;
        case 'rejected': $db_status = 'tu_choi'; break;
        case 'canceled': $db_status = 'da_huy'; break;
    }
    if ($db_status) {
        $where_clause .= " AND b.status = ?";
        $params[] = $db_status;
        $types .= "s";
    }
}

// Lấy toàn bộ danh sách đã lọc
$query_all = "
    SELECT b.*, r.room_code, r.room_name
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    $where_clause
    ORDER BY b.created_at DESC
";
$stmt_all = $conn->prepare($query_all);
$stmt_all->bind_param($types, ...$params);
$stmt_all->execute();
$bookings_export = $stmt_all->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_all->close();

/**
 * Hàm lấy label status
 */
function getStatusLabel($status) {
    switch($status) {
        case 'cho_duyet': return 'Chờ duyệt';
        case 'da_duyet': return 'Đã duyệt';
        case 'tu_choi': return 'Từ chối';
        case 'da_huy': return 'Đã hủy';
        default: return 'Không xác định';
    }
}

/**
 * Hàm xuất Excel
 */
function exportToExcel($bookings) {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=Lich_su_dat_phong_" . date('Ymd_His') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    ?>
    <table border="1">
        <thead>
            <tr style="background-color: #4f81bd; color: white;">
                <th>STT</th>
                <th>Mã đơn</th>
                <th>Phòng</th>
                <th>Ngày đặt</th>
                <th>Thời gian</th>
                <th>Số người</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $index => $booking): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo $booking['booking_code']; ?></td>
                    <td><?php echo $booking['room_code'] . ' - ' . $booking['room_name']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></td>
                    <td><?php echo substr($booking['start_time'], 0, 5) . ' - ' . substr($booking['end_time'], 0, 5); ?></td>
                    <td><?php echo $booking['participants']; ?></td>
                    <td><?php echo getStatusLabel($booking['status']); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    exit;
}

/**
 * Hàm xuất PDF (Client-side trigger)
 */
function exportToPDF($bookings) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Xuất PDF - Lịch sử đặt phòng</title>
        <meta charset="utf-8">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" />
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" />
        <style>
            body { font-family: 'Roboto', sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; background: #f4f6f9; }
            .loader { border: 5px solid #f3f3f3; border-top: 5px solid #3498db; border-radius: 50%; width: 50px; height: 50px; animation: spin 2s linear infinite; margin-bottom: 20px; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            h3 { color: #333; }
        </style>
    </head>
    <body>
        <div class="loader"></div>
        <h3>Đang tạo file PDF, vui lòng đợi trong giây lát...</h3>

        <div style="display: none;">
            <table id="pdfTable">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Phòng</th>
                        <th>Ngày đặt</th>
                        <th>Thời gian</th>
                        <th>Số người</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?php echo $booking['booking_code']; ?></td>
                            <td><?php echo $booking['room_code'] . ' - ' . $booking['room_name']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></td>
                            <td><?php echo substr($booking['start_time'], 0, 5) . ' - ' . substr($booking['end_time'], 0, 5); ?></td>
                            <td><?php echo $booking['participants']; ?></td>
                            <td><?php echo getStatusLabel($booking['status']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

        <script>
            $(document).ready(function() {
                var table = $('#pdfTable').DataTable({
                    dom: 'B',
                    buttons: [
                        {
                            extend: 'pdfHtml5',
                            title: 'Báo cáo Lịch sử Đặt phòng',
                            orientation: 'landscape',
                            pageSize: 'A4',
                            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] },
                            customize: function(doc) {
                                // Cấu hình font
                                doc.defaultStyle.font = 'Roboto';
                                doc.defaultStyle.fontSize = 10;

                                // Header: Tên đơn vị / Hệ thống
                                doc.content.splice(0, 1, {
                                    text: [
                                        { text: 'BÁO CÁO LỊCH SỬ ĐẶT PHÒNG\n', fontSize: 18, bold: true, color: '#1a202c' },
                                        { text: '\n', fontSize: 5 }, // Thêm khoảng cách nhỏ
                                        { text: 'Ngày xuất báo cáo: ' + new Date().toLocaleString('vi-VN'), fontSize: 10, italic: true, color: '#718096' }
                                    ],
                                    alignment: 'center',
                                    margin: [0, 0, 0, 30] // Tăng margin dưới để tách biệt với bảng
                                });

                                // Table Styling
                                var objLayout = {};
                                objLayout['hLineWidth'] = function(i) { return .5; };
                                objLayout['vLineWidth'] = function(i) { return .5; };
                                objLayout['hLineColor'] = function(i) { return '#aaa'; };
                                objLayout['vLineColor'] = function(i) { return '#aaa'; };
                                objLayout['paddingLeft'] = function(i) { return 8; };
                                objLayout['paddingRight'] = function(i) { return 8; };
                                objLayout['paddingTop'] = function(i) { return 6; };
                                objLayout['paddingBottom'] = function(i) { return 6; };
                                doc.content[1].layout = objLayout;

                                // Style cho header bảng
                                doc.content[1].table.headerRows = 1;
                                doc.content[1].table.widths = ['18%', '22%', '12%', '13%', '10%', '10%', '15%'];
                                
                                var rowCount = doc.content[1].table.body.length;
                                for (i = 0; i < rowCount; i++) {
                                    // Header row
                                    if (i === 0) {
                                        for (j = 0; j < 7; j++) {
                                            doc.content[1].table.body[i][j].fillColor = '#2563eb';
                                            doc.content[1].table.body[i][j].color = 'white';
                                            doc.content[1].table.body[i][j].bold = true;
                                            doc.content[1].table.body[i][j].alignment = 'center';
                                        }
                                    } else {
                                        // Zebra stripping
                                        if (i % 2 === 0) {
                                            for (j = 0; j < 7; j++) {
                                                doc.content[1].table.body[i][j].fillColor = '#f3f4f6';
                                            }
                                        }
                                        // Alignments
                                        doc.content[1].table.body[i][0].alignment = 'center'; // Mã đơn
                                        doc.content[1].table.body[i][2].alignment = 'center'; // Ngày đặt
                                        doc.content[1].table.body[i][4].alignment = 'center'; // Số người
                                        doc.content[1].table.body[i][5].alignment = 'center'; // Trạng thái
                                    }
                                }

                                // Footer
                                doc['footer'] = function(page, pages) {
                                    return {
                                        columns: [
                                            { text: 'Hệ thống Quản lý Phòng học', alignment: 'left', margin: [40, 0], fontSize: 8 },
                                            { text: 'Trang ' + page.toString() + ' / ' + pages.toString(), alignment: 'right', margin: [0, 0, 40, 0], fontSize: 8 }
                                        ],
                                        margin: [0, 10, 0, 0]
                                    };
                                };
                            }
                        }
                    ]
                });
                
                table.button('.buttons-pdf').trigger();
                
                setTimeout(function() {
                    window.close();
                }, 3000);
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Gọi hàm dựa trên yêu cầu
if ($type === 'excel') {
    exportToExcel($bookings_export);
} elseif ($type === 'pdf') {
    exportToPDF($bookings_export);
} else {
    die("Loại xuất file không hợp lệ!");
}
?>
