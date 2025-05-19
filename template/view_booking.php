<?php
include '../config/connection.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$booking_id = $_GET['id'];

// Get booking details with user and room information
$booking_query = $conn->prepare("
    SELECT b.*, u.username, u.email, r.name as room_name, r.price, r.image, r.facilities 
    FROM bookings b
    JOIN users u ON b.user_id = u.ID_user
    JOIN rooms r ON b.room_id = r.id
    WHERE b.id = ?
");

$booking_query->bind_param("i", $booking_id);
$booking_query->execute();
$result = $booking_query->get_result();

// Check if booking exists
if ($result->num_rows === 0) {
    header("Location: admin_dashboard.php");
    exit;
}

$booking = $result->fetch_assoc();

// Calculate duration of stay
$checkin_date = new DateTime($booking['checkin_date']);
$checkout_date = new DateTime($booking['checkout_date']);
$duration = $checkout_date->diff($checkin_date)->days;

// Calculate total price
$total_price = $booking['price'] * $duration;

// Get booking status class
$status_class = '';
switch($booking['status']) {
    case 'pending':
        $status_class = 'status-pending';
        break;
    case 'confirmed':
        $status_class = 'status-confirmed';
        break;
    case 'cancelled':
        $status_class = 'status-cancelled';
        break;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Booking | Admin Dashboard</title>
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-title .back-btn {
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 1rem;
            font-weight: normal;
        }
        
        /* Booking Details Styles */
        .booking-details-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .booking-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .booking-id {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .booking-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
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
        
        .booking-content {
            padding: 20px;
        }
        
        .booking-section {
            margin-bottom: 30px;
        }
        
        .booking-section-title {
            font-size: 1.1rem;
            color: var(--dark-color);
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .booking-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .booking-info-card {
            background-color: var(--light-color);
            border-radius: 8px;
            padding: 15px;
        }
        
        .booking-info-title {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .booking-info-value {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .user-details, .room-details {
            display: flex;
            gap: 20px;
        }
        
        .room-image {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .room-info {
            flex: 1;
        }
        
        .room-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .room-facilities {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .facility-badge {
            background-color: var(--light-color);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        
        .price-details {
            margin-top: 20px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .total-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .booking-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .action-btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            color: white;
            transition: all 0.3s;
        }
        
        .confirm-btn {
            background-color: var(--success-color);
        }
        
        .confirm-btn:hover {
            background-color: #3d9630;
        }
        
        .cancel-btn {
            background-color: var(--danger-color);
        }
        
        .cancel-btn:hover {
            background-color: #c1121f;
        }
        
        .back-btn-large {
            background-color: var(--dark-color);
        }
        
        .back-btn-large:hover {
            background-color: #16181b;
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
            
            .user-details, .room-details {
                flex-direction: column;
            }
            
            .room-image {
                width: 100%;
                height: auto;
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
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_rooms.php"><i class="fas fa-hotel"></i> <span>Kelola Kamar</span></a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> <span>Kelola Pengguna</span></a></li>
                <li><a href="manage_bookings.php" class="active"><i class="fas fa-calendar-alt"></i> <span>Kelola Reservasi</span></a></li>
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

            <h2 class="page-title">
                <a href="admin_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
                Detail Reservasi #<?php echo $booking_id; ?>
            </h2>

            <div class="booking-details-container">
                <div class="booking-header">
                    <div class="booking-id">Booking ID: #<?php echo $booking_id; ?></div>
                    <div class="booking-status <?php echo $status_class; ?>">
                        <?php echo ucfirst($booking['status']); ?>
                    </div>
                </div>

                <div class="booking-content">
                    <div class="booking-section">
                        <h3 class="booking-section-title">Informasi Reservasi</h3>
                        <div class="booking-grid">
                            <div class="booking-info-card">
                                <div class="booking-info-title">Tanggal Check-in</div>
                                <div class="booking-info-value"><?php echo date('d M Y', strtotime($booking['checkin_date'])); ?></div>
                            </div>
                            <div class="booking-info-card">
                                <div class="booking-info-title">Tanggal Check-out</div>
                                <div class="booking-info-value"><?php echo date('d M Y', strtotime($booking['checkout_date'])); ?></div>
                            </div>
                            <div class="booking-info-card">
                                <div class="booking-info-title">Durasi Menginap</div>
                                <div class="booking-info-value"><?php echo $duration; ?> malam</div>
                            </div>
                            <div class="booking-info-card">
                                <div class="booking-info-title">Status</div>
                                <div class="booking-info-value <?php echo $status_class; ?>"><?php echo ucfirst($booking['status']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="booking-section">
                        <h3 class="booking-section-title">Informasi Pengguna</h3>
                        <div class="user-details">
                            <div class="booking-info-card" style="flex: 1;">
                                <div class="booking-info-title">Username</div>
                                <div class="booking-info-value"><?php echo htmlspecialchars($booking['username']); ?></div>
                            </div>
                            <div class="booking-info-card" style="flex: 1;">
                                <div class="booking-info-title">Email</div>
                                <div class="booking-info-value"><?php echo htmlspecialchars($booking['email']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="booking-section">
                        <h3 class="booking-section-title">Informasi Kamar</h3>
                        <div class="room-details">
                            <img src="../img/<?php echo $booking['image']; ?>" alt="<?php echo $booking['room_name']; ?>" class="room-image">
                            <div class="room-info">
                                <div class="room-name"><?php echo htmlspecialchars($booking['room_name']); ?></div>
                                <div class="price">Rp<?php echo number_format($booking['price'], 0, ',', '.'); ?> / malam</div>
                                
                                <div class="room-facilities">
                                    <?php
                                    $facilities = explode(',', $booking['facilities']);
                                    foreach ($facilities as $facility) {
                                        echo '<span class="facility-badge">' . trim(htmlspecialchars($facility)) . '</span>';
                                    }
                                    ?>
                                </div>
                                
                                <div class="price-details">
                                    <div class="price-row">
                                        <span>Harga per malam:</span>
                                        <span>Rp<?php echo number_format($booking['price'], 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="price-row">
                                        <span>Durasi menginap:</span>
                                        <span><?php echo $duration; ?> malam</span>
                                    </div>
                                    <div class="price-row total-price">
                                        <span>Total:</span>
                                        <span>Rp<?php echo number_format($total_price, 0, ',', '.'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($booking['status'] == 'pending'): ?>
                    <div class="booking-section">
                        <h3 class="booking-section-title">Aksi</h3>
                        <div class="booking-actions">
                            <button class="action-btn confirm-btn" id="confirmBooking" data-booking-id="<?php echo $booking_id; ?>">
                                <i class="fas fa-check"></i> Konfirmasi Reservasi
                            </button>
                            <button class="action-btn cancel-btn" id="cancelBooking" data-booking-id="<?php echo $booking_id; ?>">
                                <i class="fas fa-times"></i> Tolak Reservasi
                            </button>
                            <a href="admin_dashboard.php" class="action-btn back-btn-large">
                                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="booking-section">
                        <div class="booking-actions">
                            <a href="admin_dashboard.php" class="action-btn back-btn-large">
                                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                            </a>
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
        // Konfirmasi reservasi
        document.getElementById('confirmBooking')?.addEventListener('click', function() {
            const bookingId = this.getAttribute('data-booking-id');
            if(confirm('Apakah Anda yakin ingin mengkonfirmasi reservasi ini?')) {
                updateBookingStatus(bookingId, 'confirmed');
            }
        });
        
        // Tolak reservasi
        document.getElementById('cancelBooking')?.addEventListener('click', function() {
            const bookingId = this.getAttribute('data-booking-id');
            if(confirm('Apakah Anda yakin ingin menolak reservasi ini?')) {
                updateBookingStatus(bookingId, 'cancelled');
            }
        });
        
        // Function to update booking status
        function updateBookingStatus(bookingId, status) {
            fetch('update_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${bookingId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert(status === 'confirmed' ? 'Reservasi berhasil dikonfirmasi!' : 'Reservasi berhasil ditolak!');
                    window.location.reload();
                } else {
                    alert('Gagal mengupdate status reservasi: ' + data.message);
                }
            })
            .catch(error => {
                alert('Terjadi kesalahan: ' + error);
            });
        }
    </script>
</body>

</html>