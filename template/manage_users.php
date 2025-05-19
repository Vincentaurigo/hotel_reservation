<?php
include '../config/connection.php';
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

if (isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        
        $check = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $password, $role);
            
            if ($stmt->execute()) {
                $success = "User added successfully!";
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    else if ($_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        
        $check = $conn->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND ID_user != ?");
        $check->bind_param("ssi", $username, $email, $id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already used by another user!";
        } else {
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE ID_user = ?");
                $stmt->bind_param("ssssi", $username, $email, $password, $role, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE ID_user = ?");
                $stmt->bind_param("sssi", $username, $email, $role, $id);
            }
            
            if ($stmt->execute()) {
                $success = "User updated successfully!";
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $check_bookings = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ?");
    $check_bookings->bind_param("i", $id);
    $check_bookings->execute();
    $result = $check_bookings->get_result();
    $booking_count = $result->fetch_assoc()['count'];
    
    if ($booking_count > 0) {
        $error = "Cannot delete user with existing bookings. Please delete or reassign bookings first.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE ID_user = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "User deleted successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

$edit_user = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE ID_user = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_user = $result->fetch_assoc();
    }
    $stmt->close();
}

$query = "SELECT * FROM users ORDER BY ID_user DESC";
$users = $conn->query($query);

$admin_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
$user_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --warning-color: #fca311;
            --danger-color: #e63946;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: var(--dark-color);
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100%;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-header h2 {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-header h2 i {
            font-size: 1.5rem;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            gap: 10px;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--accent-color);
        }
        
        .sidebar-menu i {
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background-color: #c1121f;
        }
        
        .welcome-message {
            font-size: 1.2rem;
            color: var(--dark-color);
        }
        
        /* User Management Styles */
        .section-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
            color: var(--dark-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .add-user-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .add-user-btn:hover {
            background-color: #3c9d35;
        }
        
        .user-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            flex: 1;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        
        .stat-icon.blue {
            background-color: var(--primary-color);
        }
        
        .stat-icon.green {
            background-color: var(--success-color);
        }
        
        .stat-content h4 {
            margin-bottom: 5px;
            font-size: 1.8rem;
        }
        
        .stat-content p {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .user-table-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            overflow-x: auto;
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .user-table th, .user-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .user-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .user-table tr:last-child td {
            border-bottom: none;
        }
        
        .user-table .user-role {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .role-admin {
            background-color: #e6f7ff;
            color: #0070f3;
        }
        
        .role-user {
            background-color: #e6ffe6;
            color: #00a600;
        }
        
        .action-btns {
            display: flex;
            gap: 10px;
        }
        
        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .edit-btn {
            background-color: var(--primary-color);
            color: white;
        }
        
        .delete-btn {
            background-color: var(--danger-color);
            color: white;
        }
        
        /* Form Styles */
        .user-form-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .user-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .user-form {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .form-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
            background-color: white;
        }
        
        .form-actions {
            grid-column: span 2;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .form-actions {
                grid-column: span 1;
            }
        }
        
        .btn {
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #721c24;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar-header h2 span, .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a {
                justify-content: center;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .user-table th:nth-child(3), .user-table td:nth-child(3) {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-cog"></i> <span>Admin Panel</span></h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_rooms.php"><i class="fas fa-hotel"></i> <span>Kelola Kamar</span></a></li>
                <li><a href="manage_users.php" class="active"><i class="fas fa-users"></i> <span>Kelola Pengguna</span></a></li>
                <li><a href="manage_bookings.php"><i class="fas fa-calendar-alt"></i> <span>Kelola Reservasi</span></a></li>
                <li><a href="../template/upload_room.php"><i class="fas fa-upload"></i> <span>Upload Kamar</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="welcome-message">
                    Kelola Pengguna
                </div>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if(isset($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- User Statistics -->
            <div class="user-stats">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h4><?php echo $admin_count + $user_count; ?></h4>
                        <p>Total Pengguna</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content">
                        <h4><?php echo $admin_count; ?></h4>
                        <p>Admin</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-content">
                        <h4><?php echo $user_count; ?></h4>
                        <p>Pengguna Biasa</p>
                    </div>
                </div>
            </div>

            <!-- User Form (Add/Edit) -->
            <?php if(isset($_GET['action']) && $_GET['action'] == 'add' || isset($edit_user)): ?>
            <div class="user-form-container">
                <h3 class="section-title"><?php echo isset($edit_user) ? 'Edit Pengguna' : 'Tambah Pengguna Baru'; ?></h3>
                <form class="user-form" method="POST" action="">
                    <input type="hidden" name="action" value="<?php echo isset($edit_user) ? 'edit' : 'add'; ?>">
                    <?php if(isset($edit_user)): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_user['ID_user']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($edit_user) ? $edit_user['username'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($edit_user) ? $edit_user['email'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password <?php echo isset($edit_user) ? '(Kosongkan jika tidak ingin mengubah)' : ''; ?></label>
                        <input type="password" class="form-control" id="password" name="password" <?php echo isset($edit_user) ? '' : 'required'; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="user" <?php echo (isset($edit_user) && $edit_user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo (isset($edit_user) && $edit_user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <a href="manage_users.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary"><?php echo isset($edit_user) ? 'Update Pengguna' : 'Tambah Pengguna'; ?></button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            
            <!-- User Table -->
            <div class="user-table-container">
                <h3 class="section-title">
                    Daftar Pengguna
                    <a href="?action=add" class="add-user-btn"><i class="fas fa-plus"></i> Tambah Pengguna</a>
                </h3>
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users->num_rows > 0):
                            $row_number = ($page -1)  * $per_page + 1;
                            ?>
                            <?php while($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row_number++;?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="user-role role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="?edit=<?php echo $user['ID_user']; ?>" class="edit-btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?delete=<?php echo $user['ID_user']; ?>" class="delete-btn" onclick="return confirm('Yakin ingin menghapus pengguna ini?');">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Tidak ada data pengguna</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <script>
        // Hide alert messages after 3 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 3000);
    </script>
</body>

</html>