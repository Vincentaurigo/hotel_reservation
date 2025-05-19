<?php
include '../config/connection.php';
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}


$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$rooms_count = $conn->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];
$bookings_count = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$pending_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch_assoc()['count'];

$recent_bookings = $conn->query("SELECT b.*, u.username, r.name as room_name 
                              FROM bookings b
                              JOIN users u ON b.user_id = u.ID_user
                              JOIN rooms r ON b.room_id = r.id
                              ORDER BY b.id DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        
        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .card-icon.blue {
            background-color: var(--primary-color);
        }
        
        .card-icon.green {
            background-color: var(--success-color);
        }
        
        .card-icon.orange {
            background-color: var(--warning-color);
        }
        
        .card-icon.red {
            background-color: var(--danger-color);
        }
        
        .card-title {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .card-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .card-footer {
            font-size: 0.8rem;
            color: #28a745;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Quick Actions */
        .quick-actions {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
            color: var(--dark-color);
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s;
            text-align: center;
        }
        
        .action-btn:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-3px);
        }
        
        .action-btn i {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        /* Recent Bookings */
        .recent-bookings {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .booking-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .booking-item:last-child {
            border-bottom: none;
        }
        
        .booking-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .booking-content {
            flex: 1;
        }
        
        .booking-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .booking-details {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .booking-status {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .booking-actions {
            display: flex;
            gap: 10px;
        }
        
        .booking-action-btn {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .confirm-btn {
            background-color: var(--success-color);
            color: white;
        }
        
        .cancel-btn {
            background-color: var(--danger-color);
            color: white;
        }
        
        .view-btn {
            background-color: var(--primary-color);
            color: white;
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
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .booking-details {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-cog"></i> <span>Admin Panel</span></h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_rooms.php"><i class="fas fa-hotel"></i> <span>Kelola Kamar</span></a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> <span>Kelola Pengguna</span></a></li>
                <li><a href="manage_bookings.php"><i class="fas fa-calendar-alt"></i> <span>Kelola Reservasi</span></a></li>
                <li><a href="../template/upload_room.php"><i class="fas fa-upload"></i> <span>Upload Kamar</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <div class="welcome-message">
                    Selamat datang, <strong><?php echo $_SESSION['username']; ?></strong>!
                </div>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Total Kamar</div>
                            <div class="card-value"><?php echo $rooms_count; ?></div>
                        </div>
                        <div class="card-icon blue">
                            <i class="fas fa-hotel"></i>
                        </div>
                    </div>
                    <div class="card-footer">
                        <i class="fas fa-info-circle"></i> Semua kamar tersedia
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Total Pengguna</div>
                            <div class="card-value"><?php echo $users_count; ?></div>
                        </div>
                        <div class="card-icon green">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="card-footer">
                        <i class="fas fa-info-circle"></i> <?php echo ($users_count > 0 ? round($users_count / $users_count * 100) : 0); ?>% aktif
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Total Reservasi</div>
                            <div class="card-value"><?php echo $bookings_count; ?></div>
                        </div>
                        <div class="card-icon orange">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="card-footer">
                        <i class="fas fa-info-circle"></i> <?php echo $pending_bookings; ?> menunggu konfirmasi
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Pendapatan Bulan Ini</div>
                            <div class="card-value">Rp<?php 
                                // This would need your actual revenue calculation logic
                                echo number_format(0, 0, ',', '.');
                            ?></div>
                        </div>
                        <div class="card-icon red">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                    <div class="card-footer">
                        <i class="fas fa-arrow-up"></i> 8% dari bulan lalu
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3 class="section-title">Quick Actions</h3>
                <div class="action-buttons">
                    <a href="../template/upload_room.php" class="action-btn">
                        <i class="fas fa-upload"></i>
                        <span>Tambah Kamar Baru</span>
                    </a>
                    <a href="manage_users.php?action=add" class="action-btn">
                        <i class="fas fa-user-plus"></i>
                        <span>Tambah Pengguna</span>
                    </a>
                    <a href="manage_bookings.php" class="action-btn">
                        <i class="fas fa-calendar-check"></i>
                        <span>Kelola Reservasi</span>
                    </a>
                    <!-- <a href="report.php" class="action-btn">
                        <i class="fas fa-file-alt"></i>
                        <span>Generate Laporan</span>
                    </a> -->
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="recent-bookings">
                <h3 class="section-title">Reservasi Terbaru</h3>
                <div class="booking-list">
                    <?php while($booking = $recent_bookings->fetch_assoc()): ?>
                    <div class="booking-item">
                        <div class="booking-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="booking-content">
                            <div class="booking-title">
                                <?php echo htmlspecialchars($booking['username']); ?> memesan <?php echo htmlspecialchars($booking['room_name']); ?>
                            </div>
                            <div class="booking-details">
                                <span><i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($booking['checkin_date'])); ?> - <?php echo date('d M Y', strtotime($booking['checkout_date'])); ?></span>
                                <span class="booking-status status-<?php echo $booking['status']; ?>">
                                    <?php 
                                    echo ucfirst($booking['status']);
                                    if($booking['status'] == 'pending') echo ' (Butuh Konfirmasi)';
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="booking-actions">
                            <?php if($booking['status'] == 'pending'): ?>
                                <button class="booking-action-btn confirm-btn" data-booking-id="<?php echo $booking['id']; ?>">
                                    <i class="fas fa-check"></i> Konfirmasi
                                </button>
                                <button class="booking-action-btn cancel-btn" data-booking-id="<?php echo $booking['id']; ?>">
                                    <i class="fas fa-times"></i> Tolak
                                </button>
                            <?php endif; ?>
                            <button class="booking-action-btn view-btn" data-booking-id="<?php echo $booking['id']; ?>">
                                <i class="fas fa-eye"></i> Lihat
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php if($recent_bookings->num_rows == 0): ?>
                        <div class="booking-item">
                            <div class="booking-content">
                                <div class="booking-title">Tidak ada reservasi terbaru</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <script>
        // Example JavaScript for handling booking actions
        document.querySelectorAll('.confirm-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                if(confirm('Konfirmasi reservasi ini?')) {
                    // AJAX call to update booking status
                    fetch('update_booking.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${bookingId}&status=confirmed`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            location.reload();
                        } else {
                            alert('Gagal mengupdate reservasi');
                        }
                    });
                }
            });
        });
        
        document.querySelectorAll('.cancel-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                if(confirm('Batalkan reservasi ini?')) {
                    // AJAX call to update booking status
                    fetch('update_booking.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${bookingId}&status=cancelled`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            location.reload();
                        } else {
                            alert('Gagal mengupdate reservasi');
                        }
                    });
                }
            });
        });
        
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                window.location.href = `view_booking.php?id=${bookingId}`;
            });
        });
    </script>
</body>

</html>