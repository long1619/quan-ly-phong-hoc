-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 01, 2026 at 01:43 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `quan_ly_phong_hoc`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `booking_code` varchar(30) NOT NULL COMMENT 'Mã đặt phòng duy nhất',
  `room_id` int(11) NOT NULL COMMENT 'ID phòng được đặt',
  `user_id` int(11) NOT NULL COMMENT 'ID người đặt phòng',
  `booking_date` date NOT NULL COMMENT 'Ngày sử dụng phòng',
  `start_time` time NOT NULL COMMENT 'Giờ bắt đầu',
  `end_time` time NOT NULL COMMENT 'Giờ kết thúc',
  `purpose` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL COMMENT 'Mục đích sử dụng',
  `participants` int(11) DEFAULT NULL COMMENT 'Số người tham dự dự kiến',
  `contact_phone` varchar(15) DEFAULT NULL COMMENT 'Số điện thoại liên hệ',
  `notes` text DEFAULT NULL COMMENT 'Ghi chú bổ sung',
  `status` enum('cho_duyet','da_duyet','tu_choi','da_huy','hoan_thanh') DEFAULT 'cho_duyet' COMMENT 'Trạng thái đặt phòng',
  `approved_by` int(11) DEFAULT NULL COMMENT 'Người phê duyệt',
  `approved_at` timestamp NULL DEFAULT NULL COMMENT 'Thời gian phê duyệt',
  `rejection_reason` text DEFAULT NULL COMMENT 'Lý do từ chối',
  `rejected_by` int(11) DEFAULT NULL COMMENT 'ID người từ chối',
  `rejected_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm từ chối đơn',
  `cancel_reason` text DEFAULT NULL,
  `canceled_by` int(11) DEFAULT NULL COMMENT 'ID người hủy (user hoặc admin)',
  `canceled_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm hủy đơn',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Thời gian tạo đơn',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Thời gian cập nhật đơn'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lịch đặt phòng của người dùng';

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_code`, `room_id`, `user_id`, `booking_date`, `start_time`, `end_time`, `purpose`, `participants`, `contact_phone`, `notes`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `rejected_by`, `rejected_at`, `cancel_reason`, `canceled_by`, `canceled_at`, `created_at`, `updated_at`) VALUES
(1, 'BK20251228180739467', 2, 1, '2025-12-29', '03:11:00', '06:20:00', 'học nhóm', 30, '0123456789', 'không', 'cho_duyet', NULL, NULL, 'Phòng này chỉ cho trai đẹp dùng', NULL, NULL, NULL, NULL, NULL, '2025-12-28 17:07:39', '2026-01-11 16:57:27'),
(2, 'BK20251229004609960', 2, 1, '2025-12-29', '10:49:00', '11:50:00', 'dùng cho học nhóm', 10, '0362494015', 'tôi cần thêm mic và loa', 'cho_duyet', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-28 23:46:09', '2026-01-11 16:57:42'),
(3, 'BK20251229015229294', 2, 1, '2025-12-31', '01:00:00', '01:56:00', 'chơi', 10, '0362494015', 'cần thêm loa', 'tu_choi', NULL, NULL, 'Phòng này ưu tiên cho GV', NULL, NULL, NULL, NULL, NULL, '2025-12-29 00:52:29', '2026-01-03 16:42:52'),
(4, 'BK20251229015708396', 3, 1, '2025-12-31', '00:00:00', '22:59:00', 'học để ôn thi', 20, '0362494015', 'phòng này tôi cần thêm loa và máy chiếu', 'tu_choi', NULL, NULL, 'Phòng này tao đéo cho đặt', NULL, NULL, NULL, NULL, NULL, '2025-12-29 00:57:08', '2026-01-04 12:27:04'),
(5, 'BK20251230012112854', 4, 1, '2025-12-30', '09:22:00', '12:25:00', 'dùng để học', 10, '0961511206', 'cần thêm loa', 'cho_duyet', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-30 00:21:12', '2026-01-11 16:58:14'),
(6, 'BK20251230014254275', 3, 1, '2025-12-30', '11:45:00', '13:48:00', 'sử dụng để học', 20, '0856299075', 'cần thêm máy tính và máy chiếu', 'tu_choi', NULL, NULL, 'Đéo cho đặt', NULL, NULL, NULL, NULL, NULL, '2025-12-30 00:42:54', '2026-01-03 23:58:49'),
(7, 'BK20251230015756452', 7, 1, '2025-12-30', '00:00:00', '13:01:00', '222', 11, '0961511206', 'cần thêm giáo viên', 'tu_choi', NULL, NULL, 'Phòng này không duyệt cho SV', NULL, NULL, NULL, NULL, NULL, '2025-12-30 00:57:56', '2026-01-03 23:56:00'),
(8, 'BK20251230020051103', 4, 1, '2025-12-30', '01:03:00', '02:04:00', 'học nhóm', 50, '0123456789', 'không', 'cho_duyet', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-30 01:00:51', '2026-01-11 16:58:27'),
(9, 'BK20251230134437829', 5, 1, '2025-12-31', '01:49:00', '07:56:00', 'học ôn thi', 20, '0856299075', 'cần thêm máy chiếu, mic và loa', 'tu_choi', NULL, NULL, 'OK', NULL, NULL, NULL, NULL, NULL, '2025-12-30 12:44:37', '2026-01-03 17:55:24'),
(10, 'BK20251230135143624', 5, 1, '2025-12-30', '01:56:00', '23:55:00', 'học hành', 11, '0961511206', 'không', 'cho_duyet', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-30 12:51:43', '2026-01-11 16:42:20'),
(11, 'BK20260104131758158', 14, 1, '2026-01-04', '21:20:00', '23:45:00', 'Học thôi, xem phim , chơi game', 20, '0856299075', 'Cần thêm giáo viên nữ', 'da_duyet', 1, '2026-01-11 16:51:38', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-04 12:17:58', '2026-01-11 16:51:38'),
(12, 'BK20260104163209707', 15, 1, '2026-01-04', '01:34:00', '15:36:00', 'Giao lưu tiếng nhật  + học ngôn ngữ mới', 20, '0961511206', 'Cần 1 cô giáo viên xinh đẹp', 'tu_choi', NULL, NULL, 'không cho sv đặt', 1, '2026-01-11 17:05:50', NULL, NULL, NULL, '2026-01-04 15:32:09', '2026-01-11 17:05:50'),
(13, 'BK20260106015751394', 15, 1, '2026-01-06', '13:00:00', '15:00:00', 'học nhóm', 20, '0123456789', NULL, 'da_huy', NULL, NULL, NULL, NULL, NULL, 'phòng này quá bé', 1, '2026-01-11 15:29:49', '2026-01-06 00:57:51', '2026-01-11 15:29:49'),
(14, 'BK20260118132235944', 16, 1, '2026-01-18', '07:30:00', '19:20:00', 'học nhóm', 10, '0362494015', 'cần thêm tivi , loa , máy tính', 'tu_choi', NULL, NULL, 'phòng này tao xem phim xxx rồi', 1, '2026-01-31 18:19:18', NULL, NULL, NULL, '2026-01-18 12:22:35', '2026-01-31 18:19:18'),
(15, 'BK20260118133248960', 16, 1, '2026-01-19', '18:33:00', '20:30:00', 'học nhóm', 20, '0362494015', 'cần thêm tivi', 'da_duyet', 1, '2026-01-31 18:17:44', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-18 12:32:48', '2026-01-31 18:17:44'),
(16, 'BK20260201120103375', 2, 1, '2026-02-02', '08:01:00', '19:06:00', 'drrrrrrrr', 4, '0362494015', '3333333333', 'cho_duyet', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-01 11:01:03', '2026-02-01 11:01:03'),
(17, 'BK20260201120212675', 4, 1, '2026-02-01', '18:04:00', '21:00:00', 'học', 22, '0362494015', '333333333', 'cho_duyet', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-01 11:02:12', '2026-02-01 11:02:12');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL COMMENT 'ID tin tức',
  `title` varchar(255) NOT NULL COMMENT 'Tiêu đề tin tức',
  `content` text NOT NULL COMMENT 'Nội dung tin tức',
  `image_url` varchar(255) DEFAULT NULL COMMENT 'Ảnh đại diện tin tức',
  `created_by` int(11) NOT NULL COMMENT 'ID người tạo tin',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'Thời gian tạo',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Thời gian cập nhật',
  `status` enum('hien_thi','an') DEFAULT 'hien_thi' COMMENT 'Trạng thái hiển thị'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bảng lưu tin tức';

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `title`, `content`, `image_url`, `created_by`, `created_at`, `updated_at`, `status`) VALUES
(1, 'Bác sĩ gắp dị vật khó tin từ hốc mũi người bệnh', '<p><strong>Một bệnh nhân tại Cao Bằng nhập viện vì chảy máu mũi bất thường, bác sĩ phát hiện dị vật là con đỉa sống dài 10 cm ký sinh trong hốc mũi.</strong></p><figure class=\"table\"><table><tbody><tr><td><figure class=\"image\"><img style=\"aspect-ratio:1638/1095;\" src=\"https://photo.znews.vn/w660/Uploaded/gtnvzv/2026_01_18/dia_1.jpg\" alt=\"di vat anh 1\" width=\"1638\" height=\"1095\"></figure></td></tr><tr><td>Các bác sĩ gắp dị vật là đỉa sống trong mũi bệnh nhân. Ảnh: BVCC.</td></tr></tbody></table></figure><p>Mới đây, các bác sĩ Phòng khám Cấp cứu, Bệnh viện Đa khoa tỉnh <a href=\"https://znews.vn/tieu-diem/cao-bang.html\">Cao Bằng</a>, đã tiếp nhận và xử trí trường hợp dị vật trong mũi hiếm gặp, tiềm ẩn nguy cơ biến chứng nếu không được phát hiện kịp thời.</p><p>Người bệnh nhập viện trong tình trạng chảy máu mũi kéo dài, kèm cảm giác vướng và khó chịu trong hốc mũi. Qua thăm khám, các bác sĩ chuyên khoa Tai - Mũi - Họng phát hiện một con đỉa còn sống ký sinh sâu trong hốc mũi người bệnh. Dị vật sau đó được gắp ra an toàn, con đỉa có chiều dài khoảng 10 cm. Sau can thiệp, tình trạng người bệnh ổn định, không ghi nhận biến chứng.</p><p>Theo các bác sĩ, những trường hợp đỉa ký sinh trong mũi thường liên quan đến thói quen sử dụng nguồn nước tự nhiên không đảm bảo vệ sinh hoặc tiếp xúc trực tiếp với ao hồ, khe suối. Đỉa có thể bám vào niêm mạc mũi, họng, gây chảy máu kéo dài và khó phát hiện bằng mắt thường trong giai đoạn đầu.</p><p>Từ ca bệnh này, các bác sĩ khuyến cáo người dân không uống nước suối, nước khe khi chưa được đun sôi; hạn chế rửa mặt, tắm hoặc bơi lội tại ao hồ, suối tự nhiên. Khi xuất hiện các dấu hiệu bất thường như chảy máu mũi kéo dài, cảm giác có dị vật trong mũi hoặc họng, người dân cần đến ngay cơ sở y tế để được thăm khám.</p><p>Đặc biệt, người bệnh không nên tự ý ngoáy mũi hoặc cố gắp dị vật tại nhà, bởi hành động này có thể gây chảy máu nhiều và làm tình trạng trở nên nguy hiểm hơn. Việc xử trí cần được thực hiện tại các cơ sở y tế có chuyên khoa để bảo đảm an toàn.</p>', 'uploads/news/news_696cb182712e7.jpg', 1, '2026-01-14 06:02:47', '2026-01-18 17:10:10', 'hien_thi'),
(3, 'Thế giới Google News Những thành viên tiềm năng trong Hội đồng Hòa bình do Tổng thống Mỹ khởi xướng', '<p><strong>TPO - Các nhà lãnh đạo từ một số quốc gia đã nhận được thư mời tham gia Hội đồng Hòa bình do Mỹ khởi xướng, ban đầu nhằm mục đích chấm dứt xung đột ở Dải Gaza nhưng sau đó sẽ được mở rộng để giải quyết xung đột ở những nơi khác trên thế giới.</strong></p><p>Ngày 16/1, Nhà Trắng đã công bố một số thành viên của hội đồng, bao gồm Ngoại trưởng Mỹ Marco Rubio, đặc phái viên của Tổng thống Mỹ Steve Witkoff, cựu Thủ tướng Anh Tony Blair và con rể của Tổng thống Donald Trump - Jared Kushner.</p><p>Những quan chức này sẽ tiếp tục nhiệm kỳ tại hội đồng sau khi kết thúc vai trò giám sát chính quyền tạm thời tại Dải Gaza.</p><p>Theo một kế hoạch mà Nhà Trắng công bố hồi tháng 10, ông Trump sẽ là chủ tịch đầu tiên của Hội đồng Hòa bình.</p><p>Israel và nhóm vũ trang Palestine Hamas đã đồng ý với kế hoạch của Tổng thống Mỹ Trump, trong đó nêu rõ một chính quyền Palestine sẽ được giám sát bởi một hội đồng quốc tế, có nhiệm vụ giám sát việc quản trị Dải Gaza trong giai đoạn chuyển tiếp.</p><p>“Theo tôi, hội đồng sẽ bắt đầu với Dải Gaza, sau đó sẽ giải quyết các xung đột khác nếu phát sinh”, Tổng thống Mỹ Trump nói với <i>Reuters</i> trong một cuộc phỏng vấn hồi đầu tuần này.</p><p>&nbsp;</p><p><img src=\"https://cdn.tienphong.vn/images/76ebf5a83288aec3c900beb38736aaff070872e168a7a9d4e8b30eb9ba4aa9a83ae14d7222ba37411fa61a4da8d872d427bad657500b3ad7b462af905f6d6f1e/ap25286631729669-1024x640.jpg\" alt=\"ap25286631729669-1024x640.jpg\" width=\"1024\" height=\"640\"><i>Tổng thống Mỹ Donald Trump tại một hội nghị về Dải Gaza ở Ai Cập, ngày 13/10/2025. (Ảnh: AP)</i></p><p>Nhà Trắng không nêu chi tiết trách nhiệm của từng thành viên trong hội đồng, chỉ xác nhận sẽ công bố thêm thành viên trong những tuần tới.</p><p>Bốn nguồn tin cho biết vào ngày 17/1, rằng các nhà lãnh đạo của Pháp, Đức, Úc và Canada nằm trong số những người được mời tham gia Hội đồng Hòa bình.</p><p>Văn phòng Tổng thống Ai Cập và Thổ Nhĩ Kỳ cũng xác nhận đã được thư mời.</p><p>Một quan chức Liên minh châu Âu (EU) cho biết Chủ tịch Ủy ban châu Âu Ursula von der Leyen đã được mời đại diện cho EU.</p><p>&nbsp;</p><p>Hội đồng cũng sẽ bao gồm tỷ phú Marc Rowan, Chủ tịch Ngân hàng Thế giới Ajay Banga và cố vấn của ông Trump - Robert Gabriel. Ông Nikolay Mladenov, cựu đặc phái viên Liên Hợp Quốc về Trung Đông, sẽ là đại diện cấp cao cho Dải Gaza.</p><p>Hai nguồn tin ngoại giao tiết lộ, thư mời cũng bao gồm một “hiến chương”.</p><p>“Đó là một ‘Liên Hợp Quốc kiểu ông Trump’, phớt lờ những nguyên tắc cơ bản của Hiến chương Liên Hợp Quốc”, một nhà ngoại giao nắm rõ nội dung thư mời cho biết, nói thêm rằng bức thư gọi hội đồng này là “một cách tiếp cận mới táo bạo để giải quyết xung đột toàn cầu”.</p><p>Ngoài ra, Nhà Trắng cũng đã thành lập một “Hội đồng điều hành Dải Gaza” riêng biệt gồm 11 thành viên, trong đó có Ngoại trưởng Thổ Nhĩ Kỳ Hakan Fidan, điều phối viên hòa bình Trung Đông của Liên Hợp Quốc Sigrid Kaag, Bộ trưởng Hợp tác Quốc tế Các Tiểu vương quốc Ả-rập Thống nhất Reem Al-Hashimy và tỷ phú người Israel gốc CH Síp Yakir Gabay.</p><p>Tuy nhiên, Văn phòng Thủ tướng Israel Benjamin Netanyahu nhấn mạnh thành phần của hội đồng không được thảo luận với Israel và trái với chính sách của nước này, dường như ám chỉ sự hiện diện của ông Fidan vì Israel phản đối sự can thiệp của Thổ Nhĩ Kỳ.</p>', 'uploads/news/news_696cb3b295e8e.jpg', 1, '2026-01-18 17:19:30', '2026-01-18 17:19:30', 'hien_thi');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `user_id` int(11) NOT NULL COMMENT 'Người nhận thông báo',
  `booking_id` int(11) DEFAULT NULL COMMENT 'Liên quan tới đặt phòng nào',
  `type` enum('duyet','tu_choi','nhac_nho','thay_doi','huy') NOT NULL COMMENT 'Loại thông báo',
  `title` varchar(200) DEFAULT NULL COMMENT 'Tiêu đề thông báo',
  `message` text DEFAULT NULL COMMENT 'Nội dung thông báo',
  `is_read` tinyint(1) DEFAULT 0 COMMENT 'Đã đọc chưa',
  `is_sent_email` tinyint(1) DEFAULT 0 COMMENT 'Đã gửi email chưa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Thời gian tạo thông báo',
  `read_at` timestamp NULL DEFAULT NULL COMMENT 'Thời gian đọc thông báo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Thông báo gửi cho người dùng';

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `role` enum('admin','giang_vien','sinh_vien') NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `role`, `permission_key`, `active`, `created_at`, `updated_at`) VALUES
(1, 'giang_vien', 'view_room_type', 1, '2026-01-17 09:33:14', '2026-01-18 00:46:53'),
(2, 'giang_vien', 'add_room_type', 1, '2026-01-17 09:33:14', '2026-01-18 00:46:53'),
(3, 'giang_vien', 'edit_room_type', 1, '2026-01-17 09:33:14', '2026-01-18 00:46:53'),
(4, 'giang_vien', 'delete_room_type', 1, '2026-01-17 09:33:14', '2026-01-18 00:46:53'),
(5, 'sinh_vien', 'view_room_type', 1, '2026-01-17 09:33:14', '2026-01-18 00:46:53'),
(6, 'sinh_vien', 'add_room_type', 0, '2026-01-17 09:33:14', '2026-01-18 00:46:53'),
(7, 'sinh_vien', 'edit_room_type', 0, '2026-01-17 09:33:14', '2026-01-18 00:46:53'),
(8, 'sinh_vien', 'delete_room_type', 0, '2026-01-17 09:33:14', '2026-01-17 11:38:24'),
(9, 'giang_vien', 'view_room', 1, '2026-01-17 09:33:14', '2026-01-18 00:46:53'),
(10, 'giang_vien', 'add_room', 1, '2026-01-17 09:33:14', '2026-01-18 00:46:53'),
(11, 'giang_vien', 'edit_room', 1, '2026-01-17 09:33:14', '2026-01-18 00:46:53'),
(12, 'giang_vien', 'delete_room', 1, '2026-01-17 09:33:14', '2026-01-18 00:46:53'),
(13, 'sinh_vien', 'view_room', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:53'),
(14, 'sinh_vien', 'add_room', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:53'),
(15, 'sinh_vien', 'edit_room', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:53'),
(16, 'sinh_vien', 'delete_room', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:54'),
(17, 'giang_vien', 'create_booking', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:53'),
(18, 'giang_vien', 'cancel_booking', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:53'),
(19, 'giang_vien', 'approve_booking', 0, '2026-01-17 09:33:15', '2026-01-17 10:27:22'),
(20, 'giang_vien', 'view_history', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:53'),
(21, 'sinh_vien', 'create_booking', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:54'),
(22, 'sinh_vien', 'cancel_booking', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:54'),
(23, 'sinh_vien', 'approve_booking', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:54'),
(24, 'sinh_vien', 'view_history', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:54'),
(25, 'giang_vien', 'view_user', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:53'),
(26, 'giang_vien', 'add_user', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:53'),
(27, 'giang_vien', 'edit_user', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:53'),
(28, 'giang_vien', 'delete_user', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:53'),
(29, 'sinh_vien', 'view_user', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:54'),
(30, 'sinh_vien', 'add_user', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:54'),
(31, 'sinh_vien', 'edit_user', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:54'),
(32, 'sinh_vien', 'delete_user', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:54'),
(33, 'giang_vien', 'view_news', 0, '2026-01-17 09:33:15', '2026-01-17 10:27:22'),
(34, 'giang_vien', 'use_ai', 0, '2026-01-17 09:33:15', '2026-01-17 10:27:22'),
(35, 'sinh_vien', 'view_news', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:54'),
(36, 'sinh_vien', 'use_ai', 1, '2026-01-17 09:33:15', '2026-01-18 00:46:54'),
(37, 'sinh_vien', 'add_news', 1, '2026-01-17 10:19:47', '2026-01-18 00:46:54'),
(38, 'sinh_vien', 'edit_news', 1, '2026-01-17 10:32:44', '2026-01-18 00:46:54'),
(39, 'sinh_vien', 'delete_news', 1, '2026-01-17 10:32:44', '2026-01-18 00:46:54');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `room_code` varchar(20) NOT NULL COMMENT 'Mã phòng (ví dụ A101)',
  `room_name` varchar(100) NOT NULL COMMENT 'Tên phòng',
  `type_id` int(11) NOT NULL COMMENT 'ID loại phòng',
  `building` varchar(50) DEFAULT NULL COMMENT 'Tên tòa nhà',
  `floor` int(11) DEFAULT NULL COMMENT 'Tầng của phòng',
  `capacity` int(11) DEFAULT NULL COMMENT 'Sức chứa tối đa',
  `facilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Danh sách thiết bị (định dạng JSON)' CHECK (json_valid(`facilities`)),
  `status` enum('trong','dang_su_dung','bao_tri') DEFAULT 'trong' COMMENT 'Trạng thái hoạt động của phòng',
  `image_url` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn ảnh phòng',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Phòng còn hoạt động hay không',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Thời gian tạo',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Thời gian cập nhật'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh sách phòng học, phòng lab, phòng họp';

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_code`, `room_name`, `type_id`, `building`, `floor`, `capacity`, `facilities`, `status`, `image_url`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'AB202', 'Phòng A202', 10, 'Tòa A', 2, 60, '[\"Máy chiếu\",\"bảng\",\"các máy tính\"]', 'trong', 'uploads/rooms/1766535099_download.jpg', 1, '2025-12-24 00:11:39', '2025-12-28 04:09:02'),
(3, 'C301', 'Phòng C301', 10, 'Tòa C', 3, 60, '[\"máy tính\",\"máy chiếu\",\"bảng dài1\"]', 'trong', 'uploads/rooms/1766896777_images.jpg', 1, '2025-12-28 04:39:37', '2025-12-28 05:35:04'),
(4, 'A101', 'Phòng A101', 10, 'Tòa A', 1, 60, '[\"Máy chiếu\", \"Bảng trắng\", \"Điều hòa\", \"Hệ thống âm thanh\"]', 'trong', 'uploads/rooms/room_a101.jpg', 1, '2025-12-20 01:00:00', '2025-12-28 07:30:00'),
(5, 'A201', 'Phòng A201', 10, 'Tòa A', 2, 40, '[\"40 máy tính\", \"Máy chiếu\", \"Điều hòa\", \"Bảng thông minh\"]', 'trong', 'uploads/rooms/room_a201.jpg', 1, '2025-12-21 02:15:00', '2025-12-28 08:45:00'),
(6, 'B101', 'Phòng B101', 10, 'Tòa B', 1, 30, '[\"Bàn họp lớn\", \"Màn hình LED 65 inch\", \"Hệ thống hội nghị trực tuyến\", \"Micro không dây\"]', 'trong', 'uploads/rooms/room_b101.jpg', 1, '2025-12-22 03:30:00', '2026-01-18 11:51:48'),
(7, 'B201', 'Phòng B201', 10, 'Tòa B', 2, 35, '[\"Bộ thí nghiệm điện tử\", \"Dao động ký\", \"Đồng hồ vạn năng\", \"Nguồn DC\"]', 'bao_tri', 'uploads/rooms/room_b201.jpg', 1, '2025-12-23 04:00:00', '2025-12-29 09:00:00'),
(10, 'D101', 'Phòng D101', 10, 'Tòa D', 1, 80, '[\"Sân khấu mini\", \"Hệ thống ánh sáng\", \"Loa công suất lớn\", \"500 ghế ngồi\"]', 'trong', 'uploads/rooms/room_d101.jpg', 1, '2025-12-26 02:00:00', '2026-01-18 11:51:56'),
(12, 'E101', 'Phòng E101', 10, 'Tòa E', 1, 100, '[\"Bàn ghế linh hoạt\", \"2 màn hình chiếu\", \"Micro không dây\", \"Hệ thống ghi âm\"]', 'trong', 'uploads/rooms/room_e101.jpg', 1, '2025-12-28 04:30:00', '2025-12-30 07:20:00'),
(13, 'E201', 'Phòng E201', 10, 'Tòa E', 2, 45, '[\"45 máy tính có tai nghe\", \"Phần mềm học ngoại ngữ\", \"Bảng tương tác\", \"Hệ thống kiểm tra tự động\"]', 'bao_tri', 'uploads/rooms/room_e201.jpg', 1, '2025-12-29 05:00:00', '2025-12-30 08:30:00'),
(14, 'XYZ1', 'Phòng học tài liêỵ tiếng nhật', 15, 'Tòa B', 1, 100, '[\"Loa\",\"Tivi\",\"máy chiếu\",\"các thiết bị nghe nhìn\"]', 'trong', '', 1, '2026-01-04 12:15:15', '2026-01-04 12:15:15'),
(15, 'QPA1', 'Phòng dạy tiếng nhật của Quân', 16, 'Tòa A', 1, 50, '[\"Máy chiếu\",\"tivi\",\"loa\",\"âm thanh tiếng nhật\"]', 'trong', 'uploads/rooms/1767540586_download (4).jpg', 1, '2026-01-04 15:29:46', '2026-01-04 15:29:46'),
(16, 'p101', 'Phòng học mới', 10, 'Tòa A', 1, 30, '[\"tivi\",\"máy tính\"]', 'trong', 'uploads/rooms/1768738253_astract_interface.jpg', 1, '2026-01-18 12:10:53', '2026-01-18 12:10:53'),
(17, 'p102', 'phòng học mới 1', 10, 'Tòa A', 2, 40, '[\"tivi\",\"loa\"]', 'bao_tri', 'uploads/rooms/1768738365_image.jpg', 1, '2026-01-18 12:12:45', '2026-01-18 12:12:45');

-- --------------------------------------------------------

--
-- Table structure for table `room_maintenance`
--

CREATE TABLE `room_maintenance` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `room_id` int(11) NOT NULL COMMENT 'ID phòng đang bảo trì',
  `start_date` date DEFAULT NULL COMMENT 'Ngày bắt đầu bảo trì',
  `end_date` date DEFAULT NULL COMMENT 'Ngày kết thúc bảo trì',
  `reason` varchar(500) DEFAULT NULL COMMENT 'Lý do bảo trì',
  `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết công việc bảo trì',
  `status` enum('dang_bao_tri','hoan_thanh','huy') DEFAULT 'dang_bao_tri' COMMENT 'Trạng thái bảo trì',
  `reported_by` int(11) DEFAULT NULL COMMENT 'Người báo cáo bảo trì',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Thời gian tạo',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Thời gian cập nhật'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Thông tin các đợt bảo trì phòng học';

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `type_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Tên loại phòng',
  `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết về loại phòng',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Thời gian tạo bản ghi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Loại phòng (phòng học, phòng lab, phòng họp...)';

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`id`, `type_name`, `description`, `created_at`) VALUES
(6, 'Phòng học lý thuyết 122', 'Phòng học2', '2025-12-22 23:59:17'),
(8, 'Phòng hội thảo', 'Phòng này chỉ tổ chức hội thảo', '2025-12-23 00:13:18'),
(9, 'Phòng học phổ thông', '', '2025-12-23 00:35:11'),
(10, 'Phòng máy tính', '', '2025-12-23 00:35:47'),
(11, 'Phòng lab1', 'phòng thí nghiệm', '2026-01-01 11:07:49'),
(12, 'Phòng lab2222', '', '2026-01-01 11:08:11'),
(13, 'Phòng Học Thường', 'phòng học chất lượng cao', '2026-01-01 11:10:48'),
(15, 'phòng xem éc', 'âm thanh sống động', '2026-01-04 12:13:39'),
(16, 'Phòng IT chất lượng cao 1', 'Phòng này chỉ đào tạo học tiếng nhật1', '2026-01-04 15:24:53'),
(17, 'Phòng học mới xây', 'Phòng học mới xây vào tháng 1/2026', '2026-01-17 08:43:55'),
(20, 'r5r', 'r', '2026-02-01 10:56:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `username` varchar(50) NOT NULL COMMENT 'Tên đăng nhập duy nhất',
  `password` varchar(255) NOT NULL COMMENT 'Mật khẩu sau khi mã hóa',
  `avatar` varchar(255) DEFAULT NULL COMMENT 'avatar',
  `full_name` varchar(100) DEFAULT NULL COMMENT 'Họ tên đầy đủ',
  `email` varchar(100) DEFAULT NULL COMMENT 'Email người dùng (duy nhất)',
  `phone` varchar(15) DEFAULT NULL COMMENT 'Số điện thoại',
  `role` enum('admin','giang_vien','sinh_vien') NOT NULL COMMENT 'Vai trò người dùng',
  `department` varchar(100) DEFAULT NULL COMMENT 'Khoa hoặc phòng ban',
  `student_code` varchar(20) DEFAULT NULL COMMENT 'Mã sinh viên (nếu là sinh viên)',
  `employee_code` varchar(20) DEFAULT NULL COMMENT 'Mã giảng viên (nếu là giảng viên)',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Còn hoạt động hay không',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Thời gian tạo bản ghi',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Thời gian cập nhật bản ghi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bảng lưu thông tin người dùng';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `avatar`, `full_name`, `email`, `phone`, `role`, `department`, `student_code`, `employee_code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'uploads/avatars/avatar_693d9bd2863c7.jpg', 'System Administrator', 'admin@system.com', '0123456789', 'admin', 'IT Department', '', '', 1, '2025-12-09 16:55:50', '2026-01-08 03:05:18'),
(23, 'nva1', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'uploads/avatars/avatar_695f1d4480bf5.jpg', 'Nguyễn Văn A', 'nva@gmail.com', '0362494015', 'sinh_vien', 'IT cntt1', 'SVT0011234', '', 1, '2026-01-08 02:58:12', '2026-01-08 02:58:12'),
(24, 'nvb123', '$2y$10$gZNo3MrlvL9FqNmCTfMgpuGP7IZG12MkZD4sYzWExlR78HDb1tqnm', NULL, 'Nguyễn Văn B', 'nvb@gmail.com', '0362494015', 'sinh_vien', 'Kế toán', 'KT123', '', 1, '2026-01-11 17:12:41', '2026-01-17 09:43:51'),
(25, 'gv_nva', '$2y$10$IsbPdr3i7Mrnc1RshVdMBeNlb3U.E6CZYi01RfgQopYnMOHu0b.rK', 'uploads/avatars/avatar_6963da48d6b71.png', 'Nguyễn Văn A GV', 'nvaGV@gmail.com', '0339642029', 'giang_vien', 'Khoa toán', '', 'GV123', 1, '2026-01-11 17:13:44', '2026-01-17 09:45:21'),
(26, 'sv_001new', 'f91d8f69c042267444b74cc0b3c747757eb0e065', NULL, 'Long Phạm Đức', 'duclong1619@gmail.com', '0362494015', 'sinh_vien', 'IT Department', 'SV00311', '', 1, '2026-01-17 09:46:44', '2026-01-17 22:47:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_code` (`booking_code`),
  ADD KEY `fk_booking_room` (`room_id`),
  ADD KEY `fk_booking_user` (`user_id`),
  ADD KEY `fk_booking_approved_by` (`approved_by`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notify_user` (`user_id`),
  ADD KEY `fk_notify_booking` (`booking_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role`,`permission_key`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_code` (`room_code`),
  ADD KEY `fk_rooms_type` (`type_id`);

--
-- Indexes for table `room_maintenance`
--
ALTER TABLE `room_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_maintenance_room` (`room_id`),
  ADD KEY `fk_maintenance_user` (`reported_by`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID tin tức', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `room_maintenance`
--
ALTER TABLE `room_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_booking_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_booking_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `news_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notify_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `fk_notify_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_rooms_type` FOREIGN KEY (`type_id`) REFERENCES `room_types` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `room_maintenance`
--
ALTER TABLE `room_maintenance`
  ADD CONSTRAINT `fk_maintenance_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `fk_maintenance_user` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
