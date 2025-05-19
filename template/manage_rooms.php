<?php
include '../config/connection.php';
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Delete room
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];

    // Check if room exists
    $check = $conn->prepare("SELECT image FROM rooms WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Delete image file if it exists
        if (file_exists("../uploads/" . $row['image'])) {
            unlink("../uploads/" . $row['image']);
        }

        // Delete room from database
        $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $_SESSION['success'] = "Kamar berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Gagal menghapus kamar!";
        }

        header("Location: manage_rooms.php");
        exit;
    }
}

// Handle search and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total rooms (for pagination)
$count_sql = "SELECT COUNT(*) as total FROM rooms";
if (!empty($search)) {
    $count_sql .= " WHERE name LIKE '%$search%' OR facilities LIKE '%$search%'";
}
$total_rooms = $conn->query($count_sql)->fetch_assoc()['total'];
$total_pages = ceil($total_rooms / $per_page);

// Get rooms data
$sql = "SELECT * FROM rooms";
if (!empty($search)) {
    $sql .= " WHERE name LIKE '%$search%' OR facilities LIKE '%$search%'";
}
$sql .= " ORDER BY id DESC LIMIT $offset, $per_page";
$rooms = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kamar - Admin Dashboard</title>
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
            --edit-color: #3db9d3;
            --edit-hover: #2a8ba1;
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

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
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

        .page-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--dark-color);
        }

        /* Table Styles */
        .data-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-box {
            position: relative;
            width: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-box input {
            padding: 10px 15px;
            /* border-radius: 5px; */
            border: none;
            border-bottom: #155724 solid 2px;
            outline: none;
            background-color: transparent;
            width: 100%;
            margin: 0 0 0 14px;
        }

        .search-box input::placeholder {
            color: #6c757d;
        }

        .search-box input:focus {
            border-bottom: #155724 solid 2px;
        }

        .search-box i {
            position: relative;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .add-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
        }

        .add-btn:hover {
            background-color: #419642;
        }

        .id-column {
            display: none;
        }

        .alert {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .data-table tbody tr:hover {
            background-color: #f1f3f5;
        }

        .data-table .image-cell {
            width: 100px;
        }

        .data-table .action-cell {
            width: 180px;
        }

        .room-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .btn-edit {
            background-color: var(--edit-color);
            color: white;
        }

        .btn-edit:hover {
            background-color: var(--edit-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-edit:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background-color: #c1121f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-delete:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background-color: #f1f3f5;
        }

        .pagination .active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination .disabled {
            color: #6c757d;
            cursor: not-allowed;
        }

        .empty-message {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow: auto;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 10% auto;
            padding: 25px;
            width: 50%;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.4s;
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: var(--dark-color);
        }

        .modal-header {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .modal-title {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }

            .sidebar-header h2 span,
            .sidebar-menu a span {
                display: none;
            }

            .sidebar-menu a {
                justify-content: center;
            }

            .main-content {
                margin-left: 70px;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .search-box {
                width: 100%;
            }

            .modal-content {
                width: 90%;
            }

            .action-btns {
                flex-direction: column;
                gap: 5px;
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
                <li><a href="manage_rooms.php" class="active"><i class="fas fa-hotel"></i> <span>Kelola Kamar</span></a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> <span>Kelola Pengguna</span></a></li>
                <li><a href="manage_bookings.php"><i class="fas fa-calendar-alt"></i> <span>Kelola Reservasi</span></a></li>
                <li><a href="../template/upload_room.php"><i class="fas fa-upload"></i> <span>Upload Kamar</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">Kelola Kamar</h1>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <!-- Room Management -->
            <div class="data-container">
                <div class="table-header">
                    <form class="search-box" action="" method="get">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Cari nama atau fasilitas..." value="<?php echo htmlspecialchars($search); ?>">
                    </form>
                    <a href="../template/upload_room.php" class="add-btn"><i class="fas fa-plus"></i> Tambah Kamar</a>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th class="image-cell">Gambar</th>
                            <th>Nama Kamar</th>
                            <th>Harga</th>
                            <th>Fasilitas</th>
                            <th class="action-cell">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($rooms->num_rows > 0):
                            $row_number = ($page - 1) * $per_page + 1;
                        ?>
                            <?php while ($room = $rooms->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row_number++; ?></td>
                                    <td class="image-cell">
                                        <img src="../uploads/<?php echo $room['image']; ?>" alt="<?php echo $room['name']; ?>" class="room-image">
                                    </td>
                                    <td><?php echo htmlspecialchars($room['name']); ?></td>
                                    <td>Rp<?php echo number_format($room['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($room['facilities']); ?></td>
                                    <td class="action-cell">
                                        <div class="action-btns">
                                            <a href="#" class="btn btn-edit" onclick="openEditModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['name']); ?>')">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button class="btn btn-delete" onclick="confirmDelete(<?php echo $room['id']; ?>)">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-message">Tidak ada data kamar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
            <div class="modal-header">
                <h2 class="modal-title">Edit Kamar</h2>
            </div>
            <div id="editModalContent">
                <!-- Content will be loaded here via JavaScript -->
                <p>Loading...</p>
            </div>
        </div>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

    <script>
        // Delete confirmation
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus kamar ini?')) {
                window.location.href = `manage_rooms.php?delete=${id}`;
            }
        }

        // Edit modal functionality
        const editModal = document.getElementById('editModal');
        const editModalContent = document.getElementById('editModalContent');

        function openEditModal(id, roomName) {
            // Show the modal with loading message
            editModal.style.display = 'block';
            editModalContent.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i><p style="margin-top: 10px;">Memuat data kamar...</p></div>';
            
            // Fetch room data using AJAX
            fetch(`get_room_data.php?id=${id}`)
                .then(response => response.text())
                .then(data => {
                    editModalContent.innerHTML = data;
                })
                .catch(error => {
                    editModalContent.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
                    // Fallback if AJAX fails - redirect to the edit page
                    setTimeout(() => {
                        window.location.href = `edit_room.php?id=${id}`;
                    }, 2000);
                });
        }

        function closeEditModal() {
            editModal.style.display = 'none';
        }

        // Close modal if clicking outside of it
        window.onclick = function(event) {
            if (event.target === editModal) {
                closeEditModal();
            }
        }

        // Close with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
            }
        });
    </script>
</body>

</html>