# TÀI LIỆU HỆ THỐNG CHI TIẾT - QUẢN LÝ PHÒNG HỌC

## 1. TỔNG QUAN HỆ THỐNG
Hệ thống **Quản lý phòng học** được thiết kế để số hóa quy trình quản lý, đăng ký và phê duyệt sử dụng phòng học, phòng họp trong tổ chức. Hệ thống phục vụ cho ba đối tượng chính: **Sinh viên/Người dùng**, **Giảng viên/Nhân viên** và **Quản trị viên**.

---

## 2. KIẾN TRÚC MÔ ĐUN
Dự án được chia thành các phân hệ chức năng chính:
1.  **Authentication (Xác thực)**: Quản lý phiên đăng nhập.
2.  **Dashboard (Tổng quan)**: Báo cáo thống kê.
3.  **Room Management (Quản lý phòng)**: CRUD thông tin phòng.
4.  **Booking System (Đặt phòng)**: Quy trình đăng ký, kiểm tra lịch và phê duyệt.
5.  **User Management (Người dùng)**: Quản lý tài khoản và phân quyền.
6.  **News (Tin tức)**: Thông báo nội bộ.
7.  **AI Assistant**: Trợ lý ảo hỗ trợ người dùng.

---

## 3. CHI TIẾT CHỨC NĂNG VÀ VALIDATE TỪNG MÀN HÌNH

### 3.1. Phân hệ Xác thực (Authentication)

#### 3.1.1. Màn hình Đăng nhập (`admin/auth/login.php`)
*   **Chức năng**:
    *   Cho phép người dùng truy cập hệ thống bằng Email hoặc Tên đăng nhập.
    *   Phân quyền truy cập dựa trên vai trò (Role) sau khi đăng nhập thành công.
*   **Quy tắc Validate (Server-side & Client-side)**:
    1.  **Email/Username**:
        *   Bắt buộc nhập (`required`).
    2.  **Mật khẩu**:
        *   Bắt buộc nhập.
    3.  **Logic xác thực**:
        *   Kiểm tra tài khoản có tồn tại trong CSDL không.
        *   So khớp mật khẩu nhập vào với mật khẩu đã mã hóa (`SHA1`) trong CSDL.
        *   Thông báo "Tài khoản hoặc mật khẩu không chính xác".(khi tài khoản hoặc mật khẩu không chính xác)

---

### 3.2. Phân hệ Quản lý Phòng (Room Management)

#### 3.2.1. Màn hình Danh sách phòng (`admin/room/list-room.php`)
*   **Chức năng**:
    *   Hiển thị bảng danh sách các phòng hiện có.
    *   Tìm kiếm phòng theo tên hoặc mã phòng.
    *   Lọc phòng theo **Trạng thái** (Trống, Đang dùng, Bảo trì) và **Loại phòng**.
    *   Nút tắt chức năng: Thêm mới, Sửa, Xóa (Chỉ Admin).

#### 3.2.2. Màn hình Thêm/Sửa Phòng (`admin/room/add-room.php`, `edit-room.php`)
*   **Chức năng**: Nhập liệu thông tin chi tiết cho phòng học.
*   **Quy tắc Validate**:
    1.  **Mã phòng (`room_code`)**:
        *   Bắt buộc nhập.
        *   **Unique**: Không được trùng với mã phòng đã tồn tại trong hệ thống.
    2.  **Tên phòng (`room_name`)**:
        *   Bắt buộc nhập.
    3.  **Loại phòng (`type_id`)**:
        *   Bắt buộc chọn từ danh sách dropdown (dữ liệu lấy từ bảng `room_types`).
    4.  **Sức chứa (`capacity`)**:
        *   Phải là số nguyên (`integer`).
        *   Giá trị phải > 0.
    5.  **Tòa nhà/Tầng**:
        *   Tầng phải là số nguyên dương.
    6.  **Ảnh phòng (`image_url`)**:
        *   Định dạng cho phép: `JPG`, `PNG`.
        <!-- *   Dung lượng tối đa: chưa check -->
        *   Nếu không upload ảnh mới khi sửa, giữ nguyên ảnh cũ.
    7.  **Tiện ích/Thiết bị (`facilities`)**:
        *   Nhập dạng chuỗi text (ví dụ: "Máy chiếu, Bảng, Loa").

#### 3.2.3. Màn hình Quản lý Loại phòng (`admin/room-type/`)
*   **Chức năng**: CRUD các danh mục loại phòng (Phòng lý thuyết, Phòng thực hành...).
*   **Validate**:
    *   **Tên loại phòng**: Bắt buộc nhập, kiểm tra trùng lặp tên.

---

### 3.3. Phân hệ Đặt phòng (Booking System)

#### 3.3.1. Màn hình Đặt phòng (`admin/booking/booking-room.php`)
Đây là màn hình quan trọng nhất với nghiệp vụ phức tạp.
*   **Chức năng**: Người dùng điền form yêu cầu sử dụng phòng.
*   **Quy tắc Validate Chặt chẽ**:
    1.  **Chọn phòng (`room_id`)**:
        *   Bắt buộc chọn.
        *   Phòng phải có trạng thái đang hoạt động (`is_active = 1`) và không trong trạng thái bảo trì.
    2.  **Ngày đặt (`booking_date`)**:
        *   Bắt buộc chọn.
        *   Phải lớn hơn hoặc bằng ngày hiện tại (Không cho phép đặt lùi ngày).
    3.  **Thời gian (`start_time`, `end_time`)**:
        *   Bắt buộc chọn.
        *   Khung giờ hoạt động cho phép: **07:00** đến **21:00**.
        *   Logic: `Giờ kết thúc` phải lớn hơn `Giờ bắt đầu`.
    4.  **Số người tham gia (`participants`)**:
        *   Bắt buộc nhập số > 0.
        *   **Capacity Check**: Số người không được vượt quá `capacity` tối đa của phòng đã chọn.
    5.  **Mục đích sử dụng**: Bắt buộc nhập.
    6.  **Kiểm tra Xung đột (Conflict Check)**:
        *   Hệ thống truy vấn CSDL bảng `bookings`.
        *   Logic: `(Start_New < End_Old) AND (End_New > Start_Old)`
        *   Nếu tìm thấy bất kỳ đơn đặt nào cùng `room_id`, cùng `booking_date` và có trạng thái `cho_duyet` hoặc `da_duyet` nằm trong khung giờ trên -> **Báo lỗi trùng lịch**.

#### 3.3.2. Màn hình Lịch phòng (`admin/booking/calendar-room.php`)
*   **Chức năng**:
    *   Hiển thị trực quan lịch trình sử dụng của tất cả các phòng dạng lưới (Schedule Grid).
    *   Phân màu theo trạng thái:
        *   Màu Vàng/Cam: Chờ duyệt.
        *   Màu Xanh lá: Đã duyệt.
        *   Màu Đỏ: Đã bảo trì/Không khả dụng.
    *   Hiển thị popup thông tin chi tiết (Người đặt, SĐT, Mục đích) khi click vào sự kiện.

#### 3.3.3. Màn hình Phê duyệt (`admin/approve/`)
*   **Chức năng**: Dành cho Admin/Ban quản lý xét duyệt đơn.
*   **Luồng xử lý**:
    1.  **Phê duyệt (Approve)**:
        *   Cập nhật trạng thái đơn sang `da_duyet`.
        *   Gửi email tự động thông báo thành công cho người dùng (qua PHPMailer).
    2.  **Từ chối (Reject)**:
        *   Hiển thị Modal yêu cầu nhập **Lý do từ chối**.
        *   **Validate**: Lý do từ chối là bắt buộc nhập.
        *   Cập nhật trạng thái sang `tu_choi`.
        *   Gửi email thông báo từ chối kèm lý do cho người dùng.

---

### 3.4. Phân hệ Quản lý Người dùng (User Management)

#### 3.4.1. Màn hình Thêm/Sửa Người dùng (`admin/user/add-user.php`)
*   **Chức năng**: Tạo tài khoản cho giảng viên, nhân viên hoặc sinh viên.
*   **Quy tắc Validate**:
    1.  **Tên đăng nhập (`username`)**:
        *   Bắt buộc nhập.
        *   Tối thiểu 4 ký tự.
        *   Chỉ chứa chữ cái, số và dấu gạch dưới (Regex: `/^[a-zA-Z0-9_]+$/`).
        *   **Unique**: Không trùng với username đã có.
    2.  **Mật khẩu**:
        *   Tối thiểu 6 ký tự.
    3.  **Email**:
        *   Đúng định dạng Email tiêu chuẩn.
        *   **Unique**: Không được trùng email đã có trong hệ thống.
    4.  **Số điện thoại**:
        *   Đúng định dạng số điện thoại
    5.  **Vai trò (`role`)**:
        *   Bắt buộc chọn (`sinh_vien`, `giang_vien`, `admin`).
    6.  **Mã số (MSSV/MSGV)**:
        *   Nếu role là sinh viên -> Bắt buộc nhập Mã SV.
        *   Nếu role là giảng viên -> Bắt buộc nhập Mã GV.
    7.  **Ảnh đại diện**: upload ảnh

---

### 3.5. Hệ thống Tin tức (News Management)

#### 3.5.1. Màn hình Đăng tin (`admin/news/add-news.php`)
*   **Chức năng**: Đăng tải thông báo, quy định sử dụng phòng.
*   **Validate**:
    1.  **Tiêu đề**: Bắt buộc nhập, độ dài tối thiểu để đảm bảo ý nghĩa.
    2.  **Nội dung**:
        *   Bắt buộc nhập.
        *   Tích hợp trình soạn thảo văn bản (CKEditor) để format nội dung.
    3.  **Hình ảnh**: Ảnh đại diện bài viết (Bắt buộc hoặc Tùy chọn tùy cấu hình), validate check đuôi file ảnh.

---

### 3.6. Trợ lý Ảo (AI Assistant)

#### 3.6.1. Màn hình Chat (`admin/ai-assistant/`)
*   **Chức năng**:
    *   Giao diện khung chat (Chatbox).
    *   Gửi câu hỏi của người dùng đến **Gemini Flash 2.5 API**.
    *   Câu hỏi liên quan đến hệ thống và ngoài hệ thống.
*   **Logic**:
    *   **Project Mode**: AI đóng vai trò thủ thư/quản lý, chỉ trả lời về phòng học.
    *   **General Mode**: AI trò chuyện thông thường.

