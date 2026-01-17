<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only admin can save
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $perms = $_POST['perm'] ?? []; // format: [role][permission_key] = 'on'

    try {
        // Start transaction
        $conn->begin_transaction();

        // We set all to active = 0 first for the roles we are managing
        $manageRoles = ['giang_vien', 'sinh_vien'];
        $rolesStr = "'" . implode("','", $manageRoles) . "'";
        $conn->query("UPDATE permissions SET active = 0 WHERE role IN ($rolesStr)");

        // Now update active = 1 for the ones that are checked
        foreach ($perms as $role => $rolePerms) {
            foreach ($rolePerms as $permKey => $value) {
                if ($value === 'on') {
                    // Check if entry exists
                    $checkStmt = $conn->prepare("SELECT id FROM permissions WHERE role = ? AND permission_key = ?");
                    $checkStmt->bind_param("ss", $role, $permKey);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();

                    if ($result->num_rows > 0) {
                        // Update
                        $updateStmt = $conn->prepare("UPDATE permissions SET active = 1 WHERE role = ? AND permission_key = ?");
                        $updateStmt->bind_param("ss", $role, $permKey);
                        $updateStmt->execute();
                    } else {
                        // Insert new if it doesn't exist (safety)
                        $insertStmt = $conn->prepare("INSERT INTO permissions (role, permission_key, active, created_at) VALUES (?, ?, 1, NOW())");
                        $insertStmt->bind_param("ss", $role, $permKey);
                        $insertStmt->execute();
                    }
                }
            }
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
