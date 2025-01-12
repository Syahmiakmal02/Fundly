<?php
session_start();
require_once(__DIR__ . '/../auth/db_config.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    $_SESSION['error'] = "You must be an admin to access this page";
    exit();
}

// Handle DELETE request for user deletion
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || (isset($_GET['action']) && $_GET['action'] === 'delete')) {
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($userId !== $_SESSION['user_id']) {  // Prevent self-deletion
        $deleteSQL = "DELETE FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($deleteSQL);
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting user']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
    }
    exit();
}

// Handle POST request for role update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $newRole = isset($_POST['role']) ? $_POST['role'] : '';
    
    if ($userId && ($newRole === 'admin' || $newRole === 'student')) {
        $updateSQL = "UPDATE users SET role = ? WHERE user_id = ?";
        $stmt = $conn->prepare($updateSQL);
        $stmt->bind_param("si", $newRole, $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating role']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID or role']);
    }
    exit();
}

// Fetch all users for the admin to manage
$sql = "SELECT user_id, email, name, role FROM users ORDER BY user_id DESC";
$result = $conn->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);

// Get some basic stats
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$student_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'];
$admin_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet"href="../css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="shortcut icon" href="../imgs/logo.png" type="image/x-icon">
</head>

<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Admin Dashboard</h1>
            <div class="admin-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="../auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p><?php echo $total_users; ?></p>
            </div>
            <div class="stat-card">
                <h3>Students</h3>
                <p><?php echo $student_users; ?></p>
            </div>
            <div class="stat-card">
                <h3>Admins</h3>
                <p><?php echo $admin_users; ?></p>
            </div>
        </div>

        <div class="users-table-container">
            <h2>User Management</h2>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($users) && !empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td class="action-buttons">
                                <button onclick="editUser(<?php echo $user['user_id']; ?>)" class="edit-btn">Edit</button>
                                <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                <button onclick="deleteUser(<?php echo $user['user_id']; ?>)" class="delete-btn">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function editUser(userId) {
            Swal.fire({
                title: 'Edit User',
                html: `
                    <form id="editForm">
                        <div class="swal2-input-container">
                            <label for="role">Role:</label>
                            <select id="role" class="swal2-input">
                                <option value="student">Student</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: 'Update',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const role = document.getElementById('role').value;
                    const formData = new FormData();
                    formData.append('user_id', userId);
                    formData.append('role', role);
                    
                    return fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Error updating user')
                        }
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`)
                    })
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('Updated!', 'User role has been updated.', 'success')
                    .then(() => {
                        window.location.reload();
                    });
                }
            });
        }

        function deleteUser(userId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`${window.location.href}?action=delete&id=${userId}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                'Deleted!',
                                'User has been deleted.',
                                'success'
                            ).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                data.message || 'There was a problem deleting the user.',
                                'error'
                            );
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>