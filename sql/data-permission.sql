-- ========================================
-- DATA MẪU CHO BẢNG PERMISSIONS
-- ========================================

-- Xóa dữ liệu cũ (nếu có)
TRUNCATE TABLE permissions;

-- ========================================
-- Module: Quản lý Danh mục Phòng
-- ========================================

-- Giảng viên - Danh mục Phòng
INSERT INTO permissions (role, permission_key, active, created_at) VALUES
('giang_vien', 'view_room_type', 1, NOW()),
('giang_vien', 'add_room_type', 0, NOW()),
('giang_vien', 'edit_room_type', 0, NOW()),
('giang_vien', 'delete_room_type', 0, NOW());

-- Sinh viên - Danh mục Phòng
INSERT INTO permissions (role, permission_key, active, created_at) VALUES
('sinh_vien', 'view_room_type', 1, NOW()),
('sinh_vien', 'add_room_type', 0, NOW()),
('sinh_vien', 'edit_room_type', 0, NOW()),
('sinh_vien', 'delete_room_type', 0, NOW());

-- ========================================
-- Module: Quản lý Phòng
-- ========================================

-- Giảng viên - Phòng
INSERT INTO permissions (role, permission_key, active, created_at) VALUES
('giang_vien', 'view_room', 1, NOW()),
('giang_vien', 'add_room', 0, NOW()),
('giang_vien', 'edit_room', 0, NOW()),
('giang_vien', 'delete_room', 0, NOW());

-- Sinh viên - Phòng
INSERT INTO permissions (role, permission_key, active, created_at) VALUES
('sinh_vien', 'view_room', 1, NOW()),
('sinh_vien', 'add_room', 0, NOW()),
('sinh_vien', 'edit_room', 0, NOW()),
('sinh_vien', 'delete_room', 0, NOW());

-- ========================================
-- Module: Đặt Lịch & Phê Duyệt
-- ========================================

-- Giảng viên - Booking
INSERT INTO permissions (role, permission_key, active, created_at) VALUES
('giang_vien', 'create_booking', 1, NOW()),
('giang_vien', 'cancel_booking', 1, NOW()),
('giang_vien', 'approve_booking', 0, NOW()),
('giang_vien', 'view_history', 1, NOW());

-- Sinh viên - Booking
INSERT INTO permissions (role, permission_key, active, created_at) VALUES
('sinh_vien', 'create_booking', 1, NOW()),
('sinh_vien', 'cancel_booking', 1, NOW()),
('sinh_vien', 'approve_booking', 0, NOW()),
('sinh_vien', 'view_history', 1, NOW());

-- ========================================
-- Module: Người Dùng
-- ========================================

-- Giảng viên - User
INSERT INTO permissions (role, permission_key, active, created_at) VALUES
('giang_vien', 'view_user', 0, NOW()),
('giang_vien', 'add_user', 0, NOW()),
('giang_vien', 'edit_user', 0, NOW()),
('giang_vien', 'delete_user', 0, NOW());

-- Sinh viên - User
INSERT INTO permissions (role, permission_key, active, created_at) VALUES
('sinh_vien', 'view_user', 0, NOW()),
('sinh_vien', 'add_user', 0, NOW()),
('sinh_vien', 'edit_user', 0, NOW()),
('sinh_vien', 'delete_user', 0, NOW());

-- ========================================
-- Module: Hệ Thống
-- ========================================

-- Giảng viên - System
INSERT INTO permissions (role, permission_key, active, created_at) VALUES
('giang_vien', 'view_news', 1, NOW()),
('giang_vien', 'use_ai', 1, NOW());

-- Sinh viên - System
INSERT INTO permissions (role, permission_key, active, created_at) VALUES
('sinh_vien', 'view_news', 1, NOW()),
('sinh_vien', 'use_ai', 1, NOW());