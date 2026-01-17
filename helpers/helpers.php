<?php
    // Lấy thông tin người dùng theo ID
    function getUserById($conn, $userId) {
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Lấy tất cả người dùng
    function getAllUsers($conn) {
        $sql = "SELECT * FROM users";
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Kiểm tra quyền của người dùng
     * $conn Đối tượng kết nối DB
     * $role Vai trò của người dùng (admin, giang_vien, sinh_vien)
     * $permKey Mã quyền cần kiểm tra
     * Trả về true nếu có quyền, ngược lại false
     */
    function checkPermission($conn, $role, $permKey) {
        // Admin luôn có quyền
        if ($role === 'admin') return true;

        $sql = "SELECT active FROM permissions WHERE role = ? AND permission_key = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $role, $permKey);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return ($result && $result['active'] == 1);
    }
?>