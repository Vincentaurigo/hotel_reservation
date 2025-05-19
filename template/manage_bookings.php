<?php
include '../config/connection.php';
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Update booking status
if (isset($_GET['action']) && $_GET['action'] == 'update' && isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];
    
    // Validate status
    if (in_array($status, ['pending', 'confirmed', 'cancelled'])) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $_SESSION['success'] = "Status reservasi berhasil diubah!";
        } else {
            $_SESSION['error'] = "Gagal mengubah status reservasi!";
        }
    } else {
        $_SESSION['error'] = "Status tidak valid!";
    }
    
    header("Location: manage_bookings.php");
    exit;
}

// Delete booking
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "Reservasi berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus reservasi!";
    }
    
    header("Location: manage_bookings.php");
    exit;
}

// Handle filters, search and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build SQL for counting total bookings
$count_sql = "SELECT COUNT(*) as total FROM bookings b
              JOIN users u ON b.user_id = u.ID_user
              JOIN rooms r ON b.room_id = r.id";

$where_clauses = [];
$params = [];
$types = "";

if (!empty($search)) {
    $search_param = "%$search%";
    $where_clauses[] = "(u.username LIKE ? OR r.name LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($status_filter)) {
    $where_clauses[] = "b.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($where_clauses)) {
    $count_sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Prepare and execute count query
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_bookings = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_bookings / $per_page);

// Build SQL for fetching bookings
$sql = "SELECT b.*, u.username, r.name as room_name, r.price
        FROM bookings b
        JOIN users u ON b.user_id = u.ID_user
        JOIN rooms r ON b.room_id = r.id";

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY b.id DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;
$types .= "ii";

// Prepare and execute bookings query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$bookings = $stmt->get_result();

// Get overall statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch_assoc()['count'],
    'confirmed' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'confirmed'")->fetch_assoc()['count'],
    'cancelled' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'cancelled'")->fetch_assoc()['count']
];

// Calculate total revenue from confirmed bookings
$revenue_query = "SELECT SUM(r.price * DATEDIFF(b.checkout_date, b.checkin_date)) as total_revenue
                  FROM bookings b
                  JOIN rooms r ON b.room_id = r.id
                  WHERE b.status = 'confirmed'";
$revenue = $conn->query($revenue_query)->fetch_assoc()['total_revenue'] ?: 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Reservasi - Admin Dashboard</title>
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

        .page-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--dark-color);
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .icon-blue {
            background-color: var(--primary-color);
        }
        
        .icon-yellow {
            background-color: var(--warning-color);
        }
        
        .icon-green {
            background-color: var(--success-color);
        }
        
        .icon-red {
            background-color: var(--danger-color);
        }
        
        /* Table and Filters */
        .bookings-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .search-box {
            position: relative;
            width: 300px;
            display: flex;
            align-items: center;
        }
        
        .search-box input {
            padding: 10px 15px 10px 40px;
            border: none;
            border-bottom: 2px solid var(--primary-color);
            background-color: transparent;
            width: 100%;
            outline: none;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            color: #6c757d;
        }
        
        .filter-options {
            display: flex;
            gap: 10px;
        }
        
        .filter-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            background-color: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .alert {
            padding: 12px 15px;
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
        
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .bookings-table th, .bookings-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .bookings-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .bookings-table tbody tr:hover {
            background-color: #f1f3f5;
        }
        
        .bookings-table .action-cell {
            width: 160px;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-btns {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            padding: 5px 10px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-confirm {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-cancel {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-view {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-confirm:hover {
            background-color: #419642;
        }
        
        .btn-cancel:hover {
            background-color: #c1121f;
        }
        
        .btn-view:hover {
            background-color: #3652cb;
        }
        
        .empty-message {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
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
        
        /* Modal styles */
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
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-30px);}
            to {opacity: 1; transform: translateY(0);}
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: var(--dark-color);
        }
        
        .booking-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .booking-detail-item {
            margin-bottom: 15px;
        }
        
        .booking-detail-label {
            font-weight: 500;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .booking-detail-value {
            font-size: 1.1rem;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
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
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
            }
            
            .filter-options {
                width: 100%;
                overflow-x: auto;
                padding-bottom: 10px;
            }
            
            .booking-details-grid {
                grid-template-columns: 1fr;
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
                <li><a href="manage_users.php"><i class="fas fa-users"></i> <span>Kelola Pengguna</span></a></li>
                <li><a href="manage_bookings.php" class="active"><i class="fas fa-calendar-alt"></i> <span>Kelola Reservasi</span></a></li>
                <li><a href="../template/upload_room.php"><i class="fas fa-upload"></i> <span>Upload Kamar</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">Kelola Reservasi</h1>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <!-- Booking Stats -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Reservasi</p>
                    </div>
                    <div class="stat-icon icon-blue">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>Menunggu Konfirmasi</p>
                    </div>
                    <div class="stat-icon icon-yellow">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?php echo $stats['confirmed']; ?></h3>
                        <p>Reservasi Dikonfirmasi</p>
                    </div>
                    <div class="stat-icon icon-green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Rp<?php echo number_format($revenue, 0, ',', '.'); ?></h3>
                        <p>Total Pendapatan</p>
                    </div>
                    <div class="stat-icon icon-red">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="bookings-container">
                <div class="table-header">
                    <form class="search-box" action="" method="get">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Cari nama pengguna atau kamar..." value="<?php echo htmlspecialchars($search); ?>">
                        <?php if(!empty($status_filter)): ?>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <?php endif; ?>
                    </form>
                    
                    <div class="filter-options">
                        <a href="manage_bookings.php" class="filter-btn <?php echo empty($status_filter) ? 'active' : ''; ?>">Semua</a>
                        <a href="manage_bookings.php?status=pending<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Menunggu</a>
                        <a href="manage_bookings.php?status=confirmed<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="filter-btn <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">Dikonfirmasi</a>
                        <a href="manage_bookings.php?status=cancelled<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="filter-btn <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">Dibatalkan</a>
                    </div>
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

                <table class="bookings-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Pengguna</th>
                            <th>Kamar</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Durasi</th>
                            <th>Total Harga</th>
                            <th>Status</th>
                            <th class="action-cell">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($bookings->num_rows > 0):
                            $row_number = ($page - 1) * $per_page + 1;
                            while ($booking = $bookings->fetch_assoc()):
                                $duration = date_diff(date_create($booking['checkin_date']), date_create($booking['checkout_date']))->days;
                                $total_price = $booking['price'] * $duration;
                        ?>
                            <tr>
                                <td><?php echo $row_number++; ?></td>
                                <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($booking['checkin_date'])); ?></td>
                                <td><?php echo date('d M Y', strtotime($booking['checkout_date'])); ?></td>
                                <td><?php echo $duration; ?> malam</td>
                                <td>Rp<?php echo number_format($total_price, 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $booking['status']; ?>">
                                        <?php 
                                        $status_text = '';
                                        switch($booking['status']) {
                                            case 'pending': 
                                                $status_text = 'Menunggu';
                                                break;
                                            case 'confirmed': 
                                                $status_text = 'Dikonfirmasi';
                                                break;
                                            case 'cancelled': 
                                                $status_text = 'Dibatalkan';
                                                break;
                                        }
                                        echo $status_text;
                                        ?>
                                    </span>
                                </td>
                                <td class="action-cell">
                                    <div class="action-btns">
                                        <?php if($booking['status'] == 'pending'): ?>
                                            <button class="btn btn-confirm" onclick="confirmBooking(<?php echo $booking['id']; ?>)">
                                                <i class="fas fa-check"></i> Konfirmasi
                                            </button>
                                            <button class="btn btn-cancel" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-view" onclick="viewBookingDetails(<?php echo $booking['id']; ?>, '<?php echo htmlspecialchars($booking['username']); ?>', '<?php echo htmlspecialchars($booking['room_name']); ?>', '<?php echo date('d M Y', strtotime($booking['checkin_date'])); ?>', '<?php echo date('d M Y', strtotime($booking['checkout_date'])); ?>', '<?php echo $duration; ?>', '<?php echo number_format($total_price, 0, ',', '.'); ?>', '<?php echo $booking['status']; ?>')">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                            <tr>
                                <td colspan="9" class="empty-message">
                                    <i class="fas fa-info-circle"></i> Tidak ada data reservasi yang ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function confirmBooking(id) {
            if (confirm('Apakah Anda yakin ingin mengonfirmasi reservasi ini?')) {
                window.location.href = 'manage_bookings.php?action=update&id=' + id + '&status=confirmed';
            }
        }

        function cancelBooking(id) {
            if (confirm('Apakah Anda yakin ingin membatalkan reservasi ini?')) {
                window.location.href = 'manage_bookings.php?action=update&id=' + id + '&status=cancelled';
            }
        }

        function viewBookingDetails(id, username, roomName, checkinDate, checkoutDate, duration, totalPrice, status) {
            const modal = document.getElementById('bookingDetailsModal');
            modal.querySelector('.modal-title').textContent = 'Detail Reservasi #' + id;
            modal.querySelector('.booking-username').textContent = username;
            modal.querySelector('.booking-room').textContent = roomName;
            modal.querySelector('.booking-checkin').textContent = checkinDate;
            modal.querySelector('.booking-checkout').textContent = checkoutDate;
            modal.querySelector('.booking-duration').textContent = duration + ' malam';
            modal.querySelector('.booking-total-price').textContent = 'Rp' + totalPrice;
            modal.querySelector('.booking-status').textContent = status;
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('bookingDetailsModal').style.display = 'none';
        }
    </script>

    <!-- Booking Details Modal -->
    <div id="bookingDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"></h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="booking-details-grid">
                    <div class="booking-detail-item">
                        <div class="booking-detail-label">Pengguna:</div>
                        <div class="booking-detail-value booking-username"></div>
                    </div>
                    <div class="booking-detail-item">
                        <div class="booking-detail-label">Kamar:</div>
                        <div class="booking-detail-value booking-room"></div>
                    </div>
                    <div class="booking-detail-item">
                        <div class="booking-detail-label">Check-in:</div>
                        <div class="booking-detail-value booking-checkin"></div>
                    </div>
                    <div class="booking-detail-item">
                        <div class="booking-detail-label">Check-out:</div>
                        <div class="booking-detail-value booking-checkout"></div>
                    </div>
                    <div class="booking-detail-item">
                        <div class="booking-detail-label">Durasi:</div>
                        <div class="booking-detail-value booking-duration"></div>
                    </div>
                    <div class="booking-detail-item">
                        <div class="booking-detail-label">Total Harga:</div>
                        <div class="booking-detail-value booking-total-price"></div>
                    </div>
                    <div class="booking-detail-item">
                        <div class="booking-detail-label">Status:</div>
                        <div class="booking-detail-value booking-status"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal()">Tutup</button>
            </div>
        </div>
    </div>
</body>

</html>